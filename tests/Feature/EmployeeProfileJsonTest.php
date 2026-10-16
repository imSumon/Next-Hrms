<?php

namespace Tests\Feature;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\EmploymentEpisode;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Str;
use Tests\EmployeeDatabaseTestCase;

class EmployeeProfileJsonTest extends EmployeeDatabaseTestCase
{
    public function test_an_authorized_viewer_reads_the_active_company_profile(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $older = $this->makeEpisode($north, $employee, '2020-01-15', '2022-06-30', 'separated');
        $current = $this->makeEpisode($north, $employee, '2023-02-01', null, 'Open');
        $this->signInFor($north, false);

        $response = $this->getJson('/api/employees/'.$employee->id.'?company_id='.$north->id)
            ->assertOk()
            ->assertJsonPath('id', $employee->id)
            ->assertJsonPath('employee_code', 'SYN-100')
            ->assertJsonPath('display_name', 'North Ada Sample')
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('can_manage_employees', false)
            ->assertJsonPath('display_name_changes', [])
            ->assertJsonPath('episodes.0.id', $older->id)
            ->assertJsonPath('episodes.0.joining_date', '2020-01-15')
            ->assertJsonPath('episodes.0.last_working_date', '2022-06-30')
            ->assertJsonPath('episodes.0.episode_status', 'separated')
            ->assertJsonPath('episodes.1.id', $current->id)
            ->assertJsonPath('episodes.1.joining_date', '2023-02-01')
            ->assertJsonPath('episodes.1.last_working_date', null)
            ->assertJsonPath('episodes.1.episode_status', 'Open');

        $this->assertSame(
            ['can_manage_employees', 'company_name', 'display_name', 'display_name_changes', 'employee_code', 'episodes', 'id'],
            collect(array_keys($response->json()))->sort()->values()->all(),
        );
        $this->assertSame(
            ['episode_status', 'id', 'joining_date', 'last_working_date'],
            collect(array_keys($response->json('episodes.0')))->sort()->values()->all(),
        );
    }

    public function test_a_profile_with_no_episodes_is_empty_rather_than_missing(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-300', 'North Empty Episodes');
        $this->signInFor($north, false);

        $this->getJson('/api/employees/'.$employee->id)
            ->assertOk()
            ->assertJsonPath('episodes', []);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');

        $this->getJson('/api/employees/'.$employee->id)
            ->assertUnauthorized()
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);

        $this->get('/api/employees/'.$employee->id)
            ->assertUnauthorized()
            ->assertDontSee('North Ada Sample');
    }

    public function test_another_companys_employee_is_not_found(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $southEmployee = $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');
        $this->makeEpisode($south, $southEmployee, '2024-04-04', null, 'Open');
        $this->signInFor($north, false);

        $this->getJson('/api/employees/'.$southEmployee->id.'?company_id='.$south->id)
            ->assertNotFound()
            ->assertJsonMissing(['display_name' => 'South Ada Sample'])
            ->assertJsonMissing(['employee_code' => 'SYN-100']);
    }

    public function test_a_missing_employee_is_not_found(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $this->signInFor($north, false);

        $this->getJson('/api/employees/'.strtoupper((string) Str::ulid()))
            ->assertNotFound();
    }

    public function test_authenticated_request_without_a_company_context_is_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);

        $this->getJson('/api/employees/'.$employee->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);

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

        $this->getJson('/api/employees/'.$employee->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'An active company must be selected.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample'])
            ->assertJsonMissing(['display_name' => 'South Ada Sample']);

        $this->assertNull(session(CompanyContext::SESSION_KEY));
    }

    public function test_an_authorized_manager_updates_the_display_name_and_records_audit(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $episode = $this->makeEpisode($north, $employee, '2023-02-01', null, 'Open');
        $user = $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])
            ->assertOk()
            ->assertJsonPath('id', $employee->id)
            ->assertJsonPath('employee_code', 'SYN-100')
            ->assertJsonPath('display_name', 'North Ada Revised')
            ->assertJsonPath('company_name', 'Synthetic North Workshop')
            ->assertJsonPath('can_manage_employees', true)
            ->assertJsonPath('episodes.0.id', $episode->id)
            ->assertJsonPath('episodes.0.episode_status', 'Open');

        $employee->refresh();
        $this->assertSame('North Ada Revised', $employee->display_name);
        $this->assertSame('SYN-100', $employee->employee_code);
        $this->assertSame($north->id, $employee->company_id);

        $event = AuditEvent::query()->where('subject_id', $employee->id)->sole();
        $this->assertSame($north->id, $event->company_id);
        $this->assertSame(AuditEvent::SUBJECT_EMPLOYEE, $event->subject_kind);
        $this->assertSame(AuditEvent::ACTION_DISPLAY_NAME_UPDATED, $event->action_kind);
        $this->assertSame($user->id, $event->acting_user_id);
        $this->assertSame(['display_name' => 'North Ada Sample'], $event->previous_value);
        $this->assertSame(['display_name' => 'North Ada Revised'], $event->new_value);
    }

    public function test_an_audit_failure_rolls_back_the_name_change(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->signInFor($north);
        $this->mock(EmployeeAuditTrail::class, function ($mock): void {
            $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('audit failed'));
        });

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertStatus(500)
            ->assertJsonMissing(['display_name' => 'North Ada Revised']);

        $this->assertSame('North Ada Sample', $employee->refresh()->display_name);
        $this->assertSame('SYN-100', $employee->employee_code);
        $this->assertSame(0, AuditEvent::query()->count());
    }

    public function test_employee_code_and_identity_cannot_be_changed(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
            'employee_code' => 'SYN-999',
            'id' => (string) Str::ulid(),
        ])->assertUnprocessable()
            ->assertJsonPath('errors.employee_code.0', 'The employee code cannot be changed. Separation does not release a code for reuse.')
            ->assertJsonPath('errors.id.0', 'The employee identity cannot be changed.');

        $employee->refresh();
        $this->assertSame('SYN-100', $employee->employee_code);
        $this->assertSame('North Ada Sample', $employee->display_name);
        $this->assertSame(0, AuditEvent::query()->count());
    }

    public function test_a_client_company_id_does_not_move_the_employee(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
            'company_id' => $south->id,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.company_id.0', 'The company is taken from the signed-in company context, not from the form.');

        $employee->refresh();
        $this->assertSame($north->id, $employee->company_id);
        $this->assertSame('North Ada Sample', $employee->display_name);
        $this->assertSame(0, Employee::query()->where('company_id', $south->id)->count());
    }

    public function test_episode_changes_are_rejected_from_the_profile_update(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $episode = $this->makeEpisode($north, $employee, '2023-02-01', null, 'Open');
        $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
            'episodes' => [[
                'id' => $episode->id,
                'episode_status' => 'separated',
                'last_working_date' => '2024-01-01',
            ]],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.episodes.0', 'Employment episodes are not changed from this profile.');

        $episode->refresh();
        $this->assertSame('Open', $episode->episode_status);
        $this->assertNull($episode->last_working_date);
        $this->assertSame('North Ada Sample', $employee->refresh()->display_name);
    }

    public function test_another_company_and_an_unknown_employee_are_not_updated(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $southEmployee = $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');
        $this->makeEpisode($south, $southEmployee, '2024-04-04', null, 'Open');
        $this->signInFor($north);

        $this->putJson('/api/employees/'.$southEmployee->id, [
            'display_name' => 'Moved Name',
        ])->assertNotFound()
            ->assertJsonMissing(['display_name' => 'South Ada Sample'])
            ->assertJsonMissing(['employee_code' => 'SYN-100']);

        $this->assertSame('South Ada Sample', $southEmployee->refresh()->display_name);

        $this->putJson('/api/employees/'.strtoupper((string) Str::ulid()), [
            'display_name' => 'Missing Name',
        ])->assertNotFound();
    }

    public function test_unauthenticated_updates_are_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertUnauthorized()
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);

        $this->assertSame('North Ada Sample', $employee->refresh()->display_name);
    }

    public function test_a_view_only_user_cannot_update_an_employee(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->signInFor($north, false);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertForbidden()
            ->assertJsonPath('message', 'Employee changes are not authorized.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);

        $this->assertSame('North Ada Sample', $employee->refresh()->display_name);
        $this->assertSame(0, AuditEvent::query()->count());
    }

    public function test_an_invalid_display_name_is_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => '   ',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.display_name.0', 'Display name is required.');

        $this->assertSame('North Ada Sample', $employee->refresh()->display_name);
        $this->assertSame(0, AuditEvent::query()->count());
    }

    public function test_an_update_without_a_company_context_is_rejected(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);

        foreach ([$north, $south] as $company) {
            CompanyMembership::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'status' => 'active',
                'can_manage_employees' => true,
            ]);
        }

        $this->flushSession();
        $this->actingAs($user);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertForbidden()
            ->assertJsonPath('message', 'An active company must be selected.')
            ->assertJsonMissing(['display_name' => 'North Ada Sample']);

        $this->assertSame('North Ada Sample', $employee->refresh()->display_name);
        $this->assertNull(session(CompanyContext::SESSION_KEY));
    }

    public function test_a_view_only_user_reads_display_name_history_in_order(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $episode = $this->makeEpisode($north, $employee, '2023-02-01', null, 'Open');
        $actor = $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertOk();
        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Current',
        ])->assertOk();

        AuditEvent::query()->create([
            'company_id' => $north->id,
            'subject_kind' => AuditEvent::SUBJECT_EPISODE,
            'subject_id' => $episode->id,
            'action_kind' => AuditEvent::ACTION_EPISODE_UPDATED,
            'acting_user_id' => $actor->id,
            'acted_at' => now(),
            'previous_value' => ['episode_status' => 'Open'],
            'new_value' => ['episode_status' => 'separated', 'last_working_date' => '2024-01-01'],
        ]);

        $this->signInFor($north, false);
        $events = AuditEvent::query()
            ->where('subject_id', $employee->id)
            ->where('action_kind', AuditEvent::ACTION_DISPLAY_NAME_UPDATED)
            ->orderBy('acted_at')
            ->orderBy('id')
            ->get();

        $response = $this->getJson('/api/employees/'.$employee->id.'?company_id='.$north->id)
            ->assertOk()
            ->assertJsonPath('display_name_changes.0.previous_display_name', 'North Ada Sample')
            ->assertJsonPath('display_name_changes.0.new_display_name', 'North Ada Revised')
            ->assertJsonPath('display_name_changes.0.actor_name', $actor->name)
            ->assertJsonPath('display_name_changes.0.acted_at', $events[0]->acted_at->format(\DateTimeInterface::ATOM))
            ->assertJsonPath('display_name_changes.1.previous_display_name', 'North Ada Revised')
            ->assertJsonPath('display_name_changes.1.new_display_name', 'North Ada Current')
            ->assertJsonPath('display_name_changes.1.actor_name', $actor->name)
            ->assertJsonCount(2, 'display_name_changes')
            ->assertJsonMissing(['episode_status' => 'separated'])
            ->assertJsonMissing(['last_working_date' => '2024-01-01']);

        $this->assertSame(
            ['acted_at', 'actor_name', 'new_display_name', 'previous_display_name'],
            collect(array_keys($response->json('display_name_changes.0')))->sort()->values()->all(),
        );
        $this->assertSame(3, AuditEvent::query()->where('company_id', $north->id)->count());
    }

    public function test_display_name_history_stays_inside_the_active_company(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $southEmployee = $this->makeEmployee($south, 'SYN-100', 'South Ada Sample');
        $actor = $this->signInFor($north);

        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertOk();

        AuditEvent::query()->create([
            'company_id' => $south->id,
            'subject_kind' => AuditEvent::SUBJECT_EMPLOYEE,
            'subject_id' => $employee->id,
            'action_kind' => AuditEvent::ACTION_DISPLAY_NAME_UPDATED,
            'acting_user_id' => $actor->id,
            'acted_at' => now(),
            'previous_value' => ['display_name' => 'South Secret Previous'],
            'new_value' => ['display_name' => 'South Secret Current'],
        ]);

        $this->getJson('/api/employees/'.$employee->id.'?company_id='.$south->id)
            ->assertOk()
            ->assertJsonCount(1, 'display_name_changes')
            ->assertJsonPath('display_name_changes.0.new_display_name', 'North Ada Revised')
            ->assertJsonMissing(['new_display_name' => 'South Secret Current'])
            ->assertJsonMissing(['previous_display_name' => 'South Secret Previous']);

        $this->signInFor($south, false);

        $this->getJson('/api/employees/'.$employee->id.'?company_id='.$north->id)
            ->assertNotFound()
            ->assertJsonMissing(['new_display_name' => 'North Ada Revised'])
            ->assertJsonMissing(['actor_name' => $actor->name]);

        $this->getJson('/api/employees/'.$southEmployee->id)
            ->assertOk()
            ->assertJsonPath('display_name_changes', [])
            ->assertJsonMissing(['new_display_name' => 'North Ada Revised']);

        $this->getJson('/api/employees/'.strtoupper((string) Str::ulid()))
            ->assertNotFound();

        $this->assertSame(1, AuditEvent::query()->where('company_id', $north->id)->count());
    }

    public function test_display_name_history_requires_a_company_context(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $employee = $this->makeEmployee($north, 'SYN-100', 'North Ada Sample');
        $actor = $this->signInFor($north);
        $this->putJson('/api/employees/'.$employee->id, [
            'display_name' => 'North Ada Revised',
        ])->assertOk();

        auth()->logout();
        $this->flushSession();

        $this->getJson('/api/employees/'.$employee->id)
            ->assertUnauthorized()
            ->assertJsonMissing(['new_display_name' => 'North Ada Revised'])
            ->assertJsonMissing(['actor_name' => $actor->name]);

        $user = User::query()->create([
            'name' => 'Synthetic Clerk',
            'email' => 'clerk-'.strtolower((string) Str::ulid()).'@example.test',
            'password' => 'synthetic-passphrase-1',
        ]);
        $this->actingAs($user);

        $this->getJson('/api/employees/'.$employee->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has no active company membership.')
            ->assertJsonMissing(['new_display_name' => 'North Ada Revised']);

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

        $this->getJson('/api/employees/'.$employee->id)
            ->assertForbidden()
            ->assertJsonPath('message', 'An active company must be selected.')
            ->assertJsonMissing(['new_display_name' => 'North Ada Revised'])
            ->assertJsonMissing(['actor_name' => $actor->name]);
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

    private function makeEpisode(Company $company, Employee $employee, string $joining, ?string $leaving, string $status): EmploymentEpisode
    {
        return EmploymentEpisode::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'joining_date' => $joining,
            'last_working_date' => $leaving,
            'episode_status' => $status,
        ]);
    }
}
