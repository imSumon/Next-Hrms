<?php

namespace Tests\Feature;

use App\Employees\EmploymentEpisodeRegistry;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmploymentEpisode;
use Tests\EmployeeDatabaseTestCase;

class EmployeeLifecycleTest extends EmployeeDatabaseTestCase
{
    public function test_created_identity_is_a_canonical_ulid_and_a_failed_episode_does_not_keep_the_employee(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $this->signInFor($company);

        $this->post('/employees', [
            'employee_code' => 'SYN-40',
            'display_name' => 'North Joan Sample',
            'joining_date' => '2026-04-01',
            'episode_status' => 'Open',
            'last_working_date' => '2026-04-15',
        ])->assertSessionHasErrors('last_working_date');

        $this->assertSame(0, Employee::query()->where('company_id', $company->id)->count());

        $this->post('/employees', [
            'employee_code' => 'SYN-40',
            'display_name' => 'North Joan Sample',
        ])->assertRedirect();

        $employee = Employee::query()->where('employee_code', 'SYN-40')->first();
        $this->assertMatchesRegularExpression('/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/', $employee->id);
        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('This identity has no separate joining date');
        $this->assertSame(0, $employee->episodes()->count());
    }

    public function test_the_create_form_explains_an_optional_first_episode(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $this->signInFor($company);

        $this->get('/employees/create')
            ->assertOk()
            ->assertSee('Employee code and display name create the employee identity.')
            ->assertSee('Joining date, episode status, and last working date belong to the first employment episode and may all be left empty.')
            ->assertSee('If you enter any of those episode details, the current validation still applies.');

        $this->post('/employees', [
            'employee_code' => 'SYN-50',
            'display_name' => 'North Quinn Sample',
            'joining_date' => '',
            'episode_status' => '',
            'last_working_date' => '',
        ])->assertRedirect();

        $identity = Employee::query()->where('employee_code', 'SYN-50')->first();
        $this->assertNotNull($identity);
        $this->assertSame(0, $identity->episodes()->count());

        $this->post('/employees', [
            'employee_code' => 'SYN-51',
            'display_name' => 'North Rhea Sample',
            'joining_date' => '2026-05-01',
            'episode_status' => 'Open',
        ])->assertRedirect();

        $withEpisode = Employee::query()->where('employee_code', 'SYN-51')->first();
        $episode = $withEpisode->episodes()->first();
        $this->assertSame('Open', $episode->episode_status);
        $this->assertSame('2026-05-01', $episode->joining_date);
        $this->assertNull($episode->last_working_date);

        $this->post('/employees', [
            'employee_code' => 'SYN-52',
            'display_name' => 'North Seth Sample',
            'joining_date' => '2026-05-02',
        ])->assertSessionHasErrors('episode_status');

        $this->post('/employees', [
            'employee_code' => 'SYN-53',
            'display_name' => 'North Tess Sample',
            'episode_status' => 'separated',
        ])->assertSessionHasErrors('joining_date');

        $this->post('/employees', [
            'employee_code' => 'SYN-54',
            'display_name' => 'North Uma Sample',
            'last_working_date' => '2026-05-03',
        ])->assertSessionHasErrors(['joining_date', 'episode_status']);

        $this->assertSame(0, Employee::query()->whereIn('employee_code', ['SYN-52', 'SYN-53', 'SYN-54'])->count());
        $this->assertSame(1, EmploymentEpisode::query()->where('company_id', $company->id)->count());

        $this->signInFor($company, false);
        $this->post('/employees', [
            'employee_code' => 'SYN-55',
            'display_name' => 'North Viewer Sample',
        ])->assertForbidden();
        $this->assertNull(Employee::query()->where('employee_code', 'SYN-55')->first());
    }

    public function test_a_non_open_episode_cannot_be_set_back_to_open(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-41', 'North Ken Sample');
        $episode = EmploymentEpisode::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'joining_date' => '2026-01-01',
            'last_working_date' => '2026-03-31',
            'episode_status' => 'separated',
        ]);
        $this->signInFor($company);

        $this->get('/employees/'.$employee->id.'/episodes/'.$episode->id.'/edit')
            ->assertOk()
            ->assertSee(EmploymentEpisodeRegistry::CLOSED_EPISODE_REOPEN_MESSAGE);

        $this->put('/employees/'.$employee->id.'/episodes/'.$episode->id, [
            'joining_date' => '2026-01-01',
            'episode_status' => 'Open',
        ])->assertSessionHasErrors([
            'episode_status' => EmploymentEpisodeRegistry::CLOSED_EPISODE_REOPEN_MESSAGE,
        ]);

        $episode->refresh();
        $this->assertSame('separated', $episode->episode_status);
        $this->assertSame('2026-03-31', $episode->last_working_date);

        $inactive = EmploymentEpisode::query()->create([
            'company_id' => $company->id,
            'employee_id' => $this->employee($company, 'SYN-41B', 'North Ken Inactive')->id,
            'joining_date' => '2026-02-01',
            'last_working_date' => '2026-02-28',
            'episode_status' => 'inactive',
        ]);

        $this->put('/employees/'.$inactive->employee_id.'/episodes/'.$inactive->id, [
            'joining_date' => '2026-02-01',
            'episode_status' => 'Open',
        ])->assertSessionHasErrors([
            'episode_status' => EmploymentEpisodeRegistry::CLOSED_EPISODE_REOPEN_MESSAGE,
        ]);

        $inactive->refresh();
        $this->assertSame('inactive', $inactive->episode_status);
        $this->assertSame('2026-02-28', $inactive->last_working_date);
    }

    public function test_a_new_episode_after_separation_preserves_the_previous_episode(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-46', 'North Pam Sample');
        $previous = EmploymentEpisode::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'joining_date' => '2026-01-01',
            'last_working_date' => '2026-03-31',
            'episode_status' => 'separated',
        ]);
        $this->signInFor($company);

        $rejoinGuidance = 'Rejoining keeps the same employee identity and employee code, creates a new employment episode, and leaves the previous episode unchanged.';

        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('Date overlap, date order, and the pay effect of inactive are not approved rules.')
            ->assertSee($rejoinGuidance)
            ->assertSee('A second Open episode is refused.')
            ->assertDontSee('does not separate or rejoin');

        $this->get('/employees/'.$employee->id.'/episodes/create')
            ->assertOk()
            ->assertSee('Adding a new episode is the approved rejoin path.')
            ->assertSee($rejoinGuidance)
            ->assertSee('A second Open episode is refused.')
            ->assertSee('Inactive has no pay rule on this screen.');

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-06-01',
            'episode_status' => 'Open',
        ])->assertRedirect();

        $previous->refresh();
        $employee->refresh();
        $open = $employee->episodes()->where('episode_status', 'Open')->first();

        $this->assertSame('separated', $previous->episode_status);
        $this->assertSame('2026-03-31', $previous->last_working_date);
        $this->assertSame($employee->id, $previous->employee_id);
        $this->assertSame('SYN-46', $employee->employee_code);
        $this->assertNotNull($open);
        $this->assertNotSame($previous->id, $open->id);

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-07-01',
            'episode_status' => 'Open',
        ])->assertSessionHasErrors('episode_status');

        $this->assertSame(1, $employee->episodes()->where('episode_status', 'Open')->count());
        $this->assertSame('separated', $previous->refresh()->episode_status);
    }

    public function test_episode_requests_cannot_retarget_another_employee_or_company(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $local = $this->employee($north, 'SYN-42', 'North Lea Sample');
        $other = $this->employee($north, 'SYN-43', 'North Max Sample');
        $foreign = $this->employee($south, 'SYN-44', 'South Nia Sample');
        $foreignEpisode = EmploymentEpisode::query()->create([
            'company_id' => $south->id,
            'employee_id' => $foreign->id,
            'joining_date' => '2026-02-01',
            'episode_status' => 'Open',
        ]);
        $this->signInFor($north);

        $this->post('/employees/'.$local->id.'/episodes', [
            'employee_id' => $other->id,
            'company_id' => $south->id,
            'joining_date' => '2026-05-01',
            'episode_status' => 'separated',
            'last_working_date' => '2026-05-20',
        ])->assertSessionHasErrors(['employee_id', 'company_id']);

        $this->assertSame(0, $local->episodes()->count());
        $this->assertSame(0, $other->episodes()->count());

        $this->put('/employees/'.$foreign->id.'/episodes/'.$foreignEpisode->id, [
            'joining_date' => '2026-02-01',
            'episode_status' => 'separated',
            'last_working_date' => '2026-02-28',
        ])->assertNotFound();

        $this->assertSame('Open', $foreignEpisode->refresh()->episode_status);
    }

    public function test_a_viewer_cannot_mutate_an_episode_and_there_is_no_delete_route(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-45', 'North Opal Sample');
        $episode = EmploymentEpisode::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'joining_date' => '2026-03-01',
            'episode_status' => 'Open',
        ]);
        $this->signInFor($company, false);

        $this->get('/employees/'.$employee->id.'/episodes/'.$episode->id.'/edit')->assertForbidden();

        $this->put('/employees/'.$employee->id.'/episodes/'.$episode->id, [
            'joining_date' => '2026-03-01',
            'episode_status' => 'separated',
            'last_working_date' => '2026-03-31',
        ])->assertForbidden();

        $this->assertSame('Open', $episode->refresh()->episode_status);
        $this->delete('/employees/'.$employee->id)->assertStatus(405);
        $this->assertNotNull($employee->refresh());
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'company_name' => $name,
            'status' => 'Active',
        ]);
    }

    private function employee(Company $company, string $code, string $name): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => $code,
            'display_name' => $name,
        ]);
    }
}
