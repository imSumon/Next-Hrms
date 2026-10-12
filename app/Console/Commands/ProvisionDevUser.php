<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\User;
use Illuminate\Console\Command;

class ProvisionDevUser extends Command
{
    protected $signature = 'hrms:provision-dev-user
        {--name= : Display name}
        {--email= : Sign-in email}
        {--password= : Password supplied at the command line}
        {--company= : Existing company id}
        {--manage-employees : Grant the employee-module write flag for that one company}';

    protected $description = 'Create one local development user and one company membership';

    public function handle(): int
    {
        if (! $this->laravel->environment('local')) {
            $this->error('This command runs only when APP_ENV is local.');

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) $this->option('password');
        $companyId = trim((string) $this->option('company'));

        if ($name === '' || $email === '' || $password === '' || $companyId === '') {
            $this->error('Name, email, password, and company are required.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('The password must be at least 12 characters.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('That email is already in use. This command does not change an existing user.');

            return self::FAILURE;
        }

        $company = Company::query()->find($companyId);
        if ($company === null) {
            $this->error('That company does not exist. This command does not create companies.');

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => 'active',
            'can_manage_employees' => (bool) $this->option('manage-employees'),
        ]);

        $this->info('Development user created for one company.');

        return self::SUCCESS;
    }
}
