<?php

namespace Tests\Feature;

use App\Employees\EmployeeRegistry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmploymentEpisode;
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

    public function test_search_treats_percent_and_underscore_as_literal_characters(): void
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

        $this->get('/employees?'.http_build_query(['q' => 'SYN-400']))
            ->assertOk()
            ->assertSee('North Ordinary Name')
            ->assertDontSee('North Percent Code')
            ->assertDontSee('North Underscore Code');

        $this->get('/employees?'.http_build_query(['q' => 'Ordinary Name']))
            ->assertOk()
            ->assertSee('North Ordinary Name')
            ->assertDontSee('North Percent Code');

        $this->get('/employees?'.http_build_query(['q' => 'SYN%100']))
            ->assertOk()
            ->assertSee('North Percent Code')
            ->assertDontSee('North Percent Decoy')
            ->assertDontSee('North Ordinary Name')
            ->assertDontSee('South Percent Code');

        $this->get('/employees?'.http_build_query(['q' => 'SYN_100']))
            ->assertOk()
            ->assertSee('North Underscore Code')
            ->assertDontSee('North Underscore Decoy')
            ->assertDontSee('North Ordinary Name');

        $this->signInFor($north, false);
        $this->get('/employees?'.http_build_query(['q' => 'SYN%100']))
            ->assertOk()
            ->assertSee('North Percent Code')
            ->assertDontSee('South Percent Code');
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
        $south = $this->company('Synthetic South Workshop');
        $this->makeEmployee($north, 'SYN-001', 'Synthetic Person 1');
        $this->makeEmployee($south, 'SYN-900', 'South Hidden Page');
        $this->signInFor($north);

        $singlePage = $this->get('/employees')->assertOk();
        $singlePage->assertSee('SYN-001');
        $singlePage->assertDontSee('South Hidden Page');
        $singlePage->assertDontSee('aria-label="Pagination"', false);

        for ($i = 2; $i <= EmployeeRegistry::PER_PAGE + 1; $i++) {
            $this->makeEmployee($north, sprintf('SYN-%03d', $i), 'Synthetic Person '.$i);
        }

        $total = EmployeeRegistry::PER_PAGE + 1;
        $firstPage = $this->get('/employees?'.http_build_query(['q' => 'SYN']))->assertOk();
        $firstHtml = $firstPage->getContent();
        $firstPage->assertSee('SYN-001')
            ->assertSee('Showing 1 to '.EmployeeRegistry::PER_PAGE.' of '.$total)
            ->assertDontSee('SYN-016')
            ->assertDontSee('South Hidden Page')
            ->assertDontSee('SYN-900');

        $this->assertSame(1, substr_count($firstHtml, 'aria-label="Pagination"'));
        $this->assertSame(1, substr_count($firstHtml, '>Previous<'));
        $this->assertSame(1, substr_count($firstHtml, '>Next<'));
        $this->assertSame(1, substr_count($firstHtml, 'aria-current="page"'));
        $this->assertStringContainsString('aria-current="page">1<', $firstHtml);
        $this->assertStringNotContainsString('sm:hidden', $firstHtml);
        $this->assertStringNotContainsString('<svg', $firstHtml);

        $this->assertSame(1, preg_match('/href="([^"]*q=SYN[^"]*page=2[^"]*)"/', $firstHtml, $pageTwo));
        $secondPage = $this->get(html_entity_decode($pageTwo[1]))->assertOk();
        $secondHtml = $secondPage->getContent();
        $secondPage->assertSee('SYN-016')
            ->assertSee('Showing '.$total.' to '.$total.' of '.$total)
            ->assertDontSee('SYN-001')
            ->assertDontSee('South Hidden Page');
        $this->assertSame(1, substr_count($secondHtml, 'aria-label="Pagination"'));
        $this->assertStringContainsString('aria-current="page">2<', $secondHtml);
        $this->assertSame(1, preg_match('/href="([^"]*q=SYN[^"]*page=1[^"]*)"/', $secondHtml, $pageOne));

        $this->get(html_entity_decode($pageOne[1]))
            ->assertOk()
            ->assertSee('SYN-001')
            ->assertDontSee('SYN-016');
    }

    public function test_create_and_edit_screens_link_back_to_the_directory_or_profile(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($company, 'SYN-30', 'North Nia Sample');
        $other = $this->makeEmployee($company, 'SYN-31', 'North Omar Sample');
        $episode = EmploymentEpisode::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'joining_date' => '2026-01-15',
            'episode_status' => 'Open',
        ]);
        $this->signInFor($company);

        $directory = route('employees.index');
        $profile = route('employees.show', $employee);
        $otherProfile = route('employees.show', $other);

        $this->assertStringNotContainsString($employee->id, $directory);
        $this->assertStringContainsString($employee->id, $profile);
        $this->assertStringNotContainsString($other->id, $profile);

        $this->get('/employees/create')
            ->assertOk()
            ->assertSee('<a href="'.$directory.'">Back to directory</a>', false);

        $this->get('/employees/'.$employee->id.'/edit')
            ->assertOk()
            ->assertSee('<a href="'.$profile.'">Back to profile</a>', false)
            ->assertDontSee($otherProfile, false);

        $this->get('/employees/'.$employee->id.'/episodes/create')
            ->assertOk()
            ->assertSee('<a href="'.$profile.'">Back to profile</a>', false)
            ->assertDontSee($otherProfile, false);

        $this->get('/employees/'.$employee->id.'/episodes/'.$episode->id.'/edit')
            ->assertOk()
            ->assertSee('<a href="'.$profile.'">Back to profile</a>', false)
            ->assertDontSee($otherProfile, false);

        $this->assertSame('North Nia Sample', $employee->refresh()->display_name);
        $this->assertSame('SYN-30', $employee->employee_code);
        $this->assertSame('Open', $episode->refresh()->episode_status);
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
