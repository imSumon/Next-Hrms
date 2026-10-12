<?php

namespace Tests;

use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

abstract class EmployeeDatabaseTestCase extends TestCase
{
    use DatabaseTransactions;

    public function createApplication()
    {
        $app = parent::createApplication();

        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql.url', null);
        $app['config']->set('database.connections.pgsql.host', '127.0.0.1');
        $app['config']->set('database.connections.pgsql.port', '5432');
        $app['config']->set('database.connections.pgsql.database', 'proedge_new_hrms_test');

        return $app;
    }

    protected function signInFor(Company $company, bool $manage = true, string $status = 'active'): User
    {
        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);

        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => $status,
            'can_manage_employees' => $manage,
        ]);

        $this->actingAs($user);
        if ($status === 'active') {
            $this->withSession([CompanyContext::SESSION_KEY => $company->id]);
        }

        return $user;
    }
}
