<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\LeavePositionWriter;
use App\Models\User;
use Illuminate\Console\Command;

class AuthorizeLeavePositionWriter extends Command
{
    protected $signature = 'hrms:authorize-leave-position-writer
        {--email= : Existing user email}
        {--company= : Existing company id}
        {--revoke : Remove the owner-named writer grant for that company}';

    protected $description = 'Grant or revoke an owner-named leave-position writer for one company. This is not a role matrix or membership flag.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->option('email')));
        $companyId = trim((string) $this->option('company'));

        if ($email === '' || $companyId === '') {
            $this->error('Email and company are required.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null) {
            $this->error('That user does not exist. This command does not create users.');

            return self::FAILURE;
        }

        $company = Company::query()->find($companyId);
        if ($company === null) {
            $this->error('That company does not exist. This command does not create companies.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            LeavePositionWriter::query()
                ->where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->delete();
            $this->info($this->grantMessage('removed', $user, $company));

            return self::SUCCESS;
        }

        LeavePositionWriter::query()->updateOrInsert(
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

    private function grantMessage(string $action, User $user, Company $company): string
    {
        return 'Leave-position writer grant '.$action.' for '.$user->email
            .' in '.$company->company_name.' ('.$company->id.'). No other company was changed.';
    }
}
