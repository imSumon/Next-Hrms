<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\PunchRecordingWriter;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuthorizePunchRecordingWriter extends Command
{
    protected $signature = 'hrms:authorize-punch-recording-writer
        {--email= : Existing user email}
        {--company= : Existing company id, company code, or unique company name}
        {--revoke : Remove the punch-recording writer grant for that company}';

    protected $description = 'Grant or revoke a company-scoped punch-recording writer. This is not a role matrix or membership flag.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->option('email')));
        $companyReference = trim((string) $this->option('company'));

        if ($email === '' || $companyReference === '') {
            $this->error('Email and company are required.');

            return self::FAILURE;
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        if ($user === null) {
            $this->error('That user does not exist. This command does not create users.');

            return self::FAILURE;
        }

        $company = $this->resolveCompany($companyReference);
        if ($company === null) {
            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            PunchRecordingWriter::query()
                ->where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->delete();
            $this->info($this->grantMessage('removed', $user, $company));

            return self::SUCCESS;
        }

        $member = CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->exists();
        if (! $member) {
            $this->error('That user does not have an active membership in that company. This command does not create memberships.');

            return self::FAILURE;
        }

        PunchRecordingWriter::query()->updateOrInsert(
            [
                'user_id' => $user->id,
                'company_id' => $company->id,
            ],
            [
                'authorized_at' => now(),
            ],
        );
        $this->info($this->grantMessage('stored', $user, $company));

        return self::SUCCESS;
    }

    private function resolveCompany(string $reference): ?Company
    {
        $byId = Company::query()->find($reference);
        if ($byId instanceof Company) {
            return $byId;
        }

        $byCode = Company::query()
            ->whereRaw('lower(company_code) = ?', [strtolower($reference)])
            ->get();
        if ($byCode->count() > 1) {
            $this->error('That company reference matches more than one company.');

            return null;
        }
        if ($byCode->count() === 1) {
            $match = $byCode->first();

            return $match instanceof Company ? $match : null;
        }

        $normalized = $this->normalize($reference);
        if ($normalized === '') {
            $this->error('That company does not exist. This command does not create companies.');

            return null;
        }

        $companies = Company::query()->get();
        $exact = $companies->filter(fn (Company $company): bool => $this->normalize($company->company_name) === $normalized);
        if ($exact->isNotEmpty()) {
            return $this->oneCompany($exact);
        }

        $prefix = $companies->filter(function (Company $company) use ($normalized): bool {
            return str_starts_with($this->normalize($company->company_name), $normalized);
        });

        return $this->oneCompany($prefix);
    }

    /**
     * @param  Collection<int, Company>  $matches
     */
    private function oneCompany(Collection $matches): ?Company
    {
        if ($matches->count() > 1) {
            $this->error('That company reference matches more than one company.');

            return null;
        }

        if ($matches->isEmpty()) {
            $this->error('That company does not exist. This command does not create companies.');

            return null;
        }

        $company = $matches->first();

        return $company instanceof Company ? $company : null;
    }

    private function normalize(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $value));
    }

    private function grantMessage(string $action, User $user, Company $company): string
    {
        return 'Punch-recording writer grant '.$action.' for '.$user->email
            .' ('.$user->id.') in '.$company->company_name.' ('.$company->id.'). No other company was changed.';
    }
}
