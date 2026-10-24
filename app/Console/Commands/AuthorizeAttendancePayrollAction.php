<?php

namespace App\Console\Commands;

use App\Models\AttendancePayrollActionGrant;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\User;
use Illuminate\Console\Command;

class AuthorizeAttendancePayrollAction extends Command
{
    protected $signature = 'hrms:authorize-attendance-payroll-action
        {--email= : Existing user email}
        {--company= : Existing company id}
        {--action= : finalize_attendance_period, reopen_attendance_period, handoff_attendance_period, review_missing_punch, or consume_attendance_handoff}
        {--revoke : Remove that one grant}';

    protected $description = 'Grant or revoke one company-scoped attendance period, missing-punch review, or payroll intake action. This is not a role hierarchy.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->option('email')));
        $companyId = trim((string) $this->option('company'));
        $action = trim((string) $this->option('action'));
        if ($email === '' || $companyId === '' || ! in_array($action, AttendancePayrollActionGrant::ACTIONS, true)) {
            $this->error('Email, company id, and a known action are required.');

            return self::FAILURE;
        }
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();
        $company = Company::query()->find($companyId);
        if ($user === null || $company === null) {
            $this->error('That user or company does not exist. This command does not create either.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            AttendancePayrollActionGrant::query()
                ->where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->where('action_key', $action)
                ->delete();
            $this->info('Grant '.$action.' removed for '.$user->email.' in '.$company->company_name.'. No other company was changed.');

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

        AttendancePayrollActionGrant::query()->updateOrInsert(
            ['user_id' => $user->id, 'company_id' => $company->id, 'action_key' => $action],
            ['authorized_at' => now()],
        );
        $this->info('Grant '.$action.' stored for '.$user->email.' in '.$company->company_name.'. No other company was changed.');

        return self::SUCCESS;
    }
}
