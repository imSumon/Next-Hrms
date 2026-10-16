<?php

namespace Tests\Feature;

use App\Employees\EmployeeRegistry;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Str;
use Tests\EmployeeDatabaseTestCase;

class EmployeeDirectoryJsonTest extends EmployeeDatabaseTestCase
{
    public function test_authenticated_company_context_returns_only_that_companys_directory(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $northEmployee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');
        $this->makeEmployee($north, 'SYN-200', 'North Ben Sample');
        $this->signInFor($north, false);

        $response = $this->getJson('/api/employees?company_id='.$south->id)
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('per_page', EmployeeRegistry::PER_PAGE)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('data.0.id', $northEmployee->id)
            ->assertJsonPath('data.0.employee_code', 'SYN-100')
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('data.0.display_name', 'North Ada Sample')
            ->assertJsonPath('data.1.display_name', 'North Ben Sample');

        $response->assertJsonMissing(['display_name' => 'South Ada Sample']);
        $response->assertJsonMissing(['company_name' => 'Synthetic South Workshop']);
        $this->assertSame(
            ['display_name', 'employee_code', 'id'],
            collect(array_keys($response->json('data.0')))->sort()->values()->all(),
        );
    }

    public function test_search_matches_the_directory_including_literal_wildcards(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->makeEmployee($north, 'SYN-400', 'North Ordinary Name');
        $this->makeEmployee($north, 'SYN%100', 'North Percent Code');
        $this->makeEmployee($north, 'SYNX100', 'North Percent Decoy');
        $this->makeEmployee($north, 'SYN_100', 'North Underscore Code');
        $this->makeEmployee($north, 'SYNA100', 'North Underscore Decoy');
        $this->makeEmployee($south, 'SYN%100', 'South Percent Code');
        $this->signInFor($north);

        $this->getJson('/api/employees?'.http_build_query(['q' => 'SYN-400', 'company_id' => $south->id]))
            ->assertOk()
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.display_name', 'North Ordinary Name')
            ->assertJsonMissing(['company_name' => 'Synthetic South Workshop'])
            ->assertJsonMissing(['display_name' => 'South Percent Code']);

        $this->getJson('/api/employees?'.http_build_query(['q' => 'Ordinary Name']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.display_name', 'North Ordinary Name');

        $this->getJson('/api/employees?'.http_build_query(['q' => 'SYN%100']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.display_name', 'North Percent Code')
            ->assertJsonMissing(['display_name' => 'North Percent Decoy'])
            ->assertJsonMissing(['display_name' => 'South Percent Code']);

        $this->getJson('/api/employees?'.http_build_query(['q' => 'SYN_100']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.display_name', 'North Underscore Code')
            ->assertJsonMissing(['display_name' => 'North Underscore Decoy']);
    }

    public function test_pagination_metadata_matches_the_directory_page_size(): void
    {
        $north = $this->company('Synthetic North Workshop');
        for ($number = 1; $number <= 16; $number++) {
            $this->makeEmployee($north, sprintf('SYN-%03d', $number), 'North Person '.$number);
        }
        $this->signInFor($north);

        $this->getJson('/api/employees')
            ->assertOk()
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', EmployeeRegistry::PER_PAGE)
            ->assertJsonPath('total', 16)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 15)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('data.0.employee_code', 'SYN-001')
            ->assertJsonPath('data.14.employee_code', 'SYN-015')
            ->assertJsonMissing(['employee_code' => 'SYN-016']);

        $this->getJson('/api/employees?page=2')
            ->assertOk()
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', EmployeeRegistry::PER_PAGE)
            ->assertJsonPath('total', 16)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('from', 16)
            ->assertJsonPath('to', 16)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_code', 'SYN-016');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');

        $this->getJson('/api/employees')
            ->assertUnauthorized()
            ->assertJsonMissing(['display_name' => 'North Ada Sample'])
            ->assertJsonMissing(['company_name' => 'Synthetic North Workshop']);

        $this->get('/api/employees')
            ->assertUnauthorized()
            ->assertDontSee('North Ada Sample');
    }

    public function test_authenticated_request_without_a_company_context_is_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);

        $this->getJson('/api/employees')
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample'])
            ->assertJsonMissing(['company_name' => 'Synthetic North Workshop']);

        foreach ([$north, $south] as $company) {
            CompanyMembership::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'status' => 'active',
                'can_manage_employees' => false,
            ]);
        }

        $this->flushSession();
        $this->actingAs($user);

        $this->getJson('/api/employees')
            ->assertForbidden()
            ->assertJsonPath('message', 'An active company must be selected.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample'])
            ->assertJsonMissing(['display_name' => 'South Ada Sample'])
            ->assertJsonMissing(['company_name' => 'Synthetic North Workshop'])
            ->assertJsonMissing(['company_name' => 'Synthetic South Workshop']);

        $this->assertNull(session(CompanyContext::SESSION_KEY));
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'company_name' => $name,
            'status' => 'Active',
        ]);
    }

    private function makeEmployee(Company $company, string $code, string $name): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => $code,
            'display_name' => $name,
        ]);
    }
}
