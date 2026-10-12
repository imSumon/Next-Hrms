<?php

namespace Tests\Feature;

use App\Employees\EmployeeRegistry;
use App\Models\Company;
use App\Models\Employee;
use Tests\EmployeeDatabaseTestCase;

class EmployeeManagementTest extends EmployeeDatabaseTestCase
{
    public function test_directory_lists_and_searches_only_the_context_company(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');
        $this->makeEmployee($north, 'SYN-200', 'North Ben Sample');

        $this->signInFor($north);

        $this->get('/employees')
            ->assertOk()
            ->assertSee('North Ada Sample')
            ->assertSee('North Ben Sample')
            ->assertDontSee('South Ada Sample');

        $this->get('/employees?q=SYN-200')
            ->assertOk()
            ->assertSee('North Ben Sample')
            ->assertDontSee('North Ada Sample');

        $this->get('/employees?q=South')
            ->assertOk()
            ->assertSee('No employees match this search.');
    }

    public function test_empty_directory_states_there_are_no_employees(): void
    {
        $this->signInFor($this->company('Synthetic Empty Workshop'));

        $this->get('/employees')
            ->assertOk()
            ->assertSee('No employees in this company.');
    }

    public function test_direct_url_for_another_company_is_not_found(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $foreign = $this->makeEmployee($south, 'SYN-9', 'South Cara Sample');
        $this->signInFor($north);

        $this->get('/employees/'.$foreign->id)->assertNotFound();
    }

    public function test_create_requires_code_and_name_and_ignores_a_submitted_company(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->signInFor($north);

        $this->post('/employees', [
            'employee_code' => '',
            'display_name' => '',
        ])->assertSessionHasErrors(['employee_code', 'display_name']);

        $this->post('/employees', [
            'company_id' => $south->id,
            'employee_code' => 'SYN-1',
            'display_name' => 'North Drew Sample',
        ])->assertSessionHasErrors('company_id');

        $this->assertSame(0, Employee::query()->count());
    }

    public function test_same_code_is_unique_inside_a_company_and_allowed_in_another(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $this->signInFor($north);

        $this->post('/employees', [
            'employee_code' => 'SYN-7',
            'display_name' => 'North Erin Sample',
            'joining_date' => '2026-02-01',
            'episode_status' => 'separated',
            'last_working_date' => '2026-02-28',
        ])->assertRedirect();

        $this->post('/employees', [
            'employee_code' => 'SYN-7',
            'display_name' => 'North Erin Copy',
        ])->assertSessionHasErrors('employee_code');

        $this->assertSame(1, Employee::query()->where('company_id', $north->id)->count());

        $this->signInFor($south);
        $this->post('/employees', [
            'employee_code' => 'SYN-7',
            'display_name' => 'South Erin Sample',
        ])->assertRedirect();

        $this->assertSame(1, Employee::query()->where('company_id', $south->id)->where('employee_code', 'SYN-7')->count());
    }

    public function test_display_name_can_change_and_employee_code_cannot(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-3', 'North Fran Sample');
        $this->signInFor($north);

        $this->put('/employees/'.$employee->id, [
            'employee_code' => 'SYN-4',
            'display_name' => 'North Fran Updated',
        ])->assertSessionHasErrors('employee_code');

        $employee->refresh();
        $this->assertSame('SYN-3', $employee->employee_code);
        $this->assertSame('North Fran Sample', $employee->display_name);

        $this->put('/employees/'.$employee->id, [
            'display_name' => 'North Fran Updated',
        ])->assertRedirect();

        $this->assertSame('North Fran Updated', $employee->refresh()->display_name);
    }

    public function test_only_one_open_episode_is_allowed_and_invalid_status_is_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-5', 'North Glen Sample');
        $this->signInFor($north);

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-03-01',
            'episode_status' => 'Open',
            'last_working_date' => '2026-03-15',
        ])->assertSessionHasErrors('last_working_date');

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-03-01',
            'episode_status' => 'active',
        ])->assertSessionHasErrors('episode_status');

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-03-01',
            'episode_status' => 'Open',
        ])->assertRedirect();

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-04-01',
            'episode_status' => 'Open',
        ])->assertSessionHasErrors('episode_status');

        $this->assertSame(1, $employee->episodes()->count());
    }

    public function test_unapproved_date_order_is_not_enforced(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-6', 'North Hope Sample');
        $this->signInFor($north);

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-06-01',
            'episode_status' => 'separated',
            'last_working_date' => '2026-01-01',
        ])->assertRedirect();

        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('Date overlap, date order, and the pay effect of inactive are not approved rules.');

        $this->assertSame('2026-01-01', $employee->episodes()->first()->last_working_date);
    }

    public function test_directory_paginates(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $this->signInFor($north);

        for ($i = 1; $i <= EmployeeRegistry::PER_PAGE + 1; $i++) {
            $this->makeEmployee($north, sprintf('SYN-%03d', $i), 'Synthetic Person '.$i);
        }

        $this->get('/employees')
            ->assertOk()
            ->assertSee('SYN-001')
            ->assertDontSee('SYN-016');

        $this->get('/employees?page=2')
            ->assertOk()
            ->assertSee('SYN-016')
            ->assertDontSee('SYN-001');
    }

    public function test_invalid_episode_reference_is_not_found(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-8', 'North Ivan Sample');
        $this->signInFor($north);

        $this->get('/employees/'.$employee->id.'/episodes/01ARZ3NDEKTSV4RRFFQ69G5FAV/edit')
            ->assertNotFound();
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
