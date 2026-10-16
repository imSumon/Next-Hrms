<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Employee;
use Tests\EmployeeDatabaseTestCase;

class EmployeeAuditTest extends EmployeeDatabaseTestCase
{
    public function test_a_display_name_change_records_one_event_and_the_profile_shows_it(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-60', 'North Ada Sample');
        $actor = $this->signInFor($company);

        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('Audit history')
            ->assertSee('No audit events.');

        $this->put('/employees/'.$employee->id, [
            'display_name' => 'North Ada Updated',
        ])->assertRedirect();

        $events = AuditEvent::query()->where('company_id', $company->id)->get();
        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame(AuditEvent::SUBJECT_EMPLOYEE, $event->subject_kind);
        $this->assertSame($employee->id, $event->subject_id);
        $this->assertSame(AuditEvent::ACTION_DISPLAY_NAME_UPDATED, $event->action_kind);
        $this->assertSame($actor->id, $event->acting_user_id);
        $this->assertSame(['display_name' => 'North Ada Sample'], $event->previous_value);
        $this->assertSame(['display_name' => 'North Ada Updated'], $event->new_value);
        $this->assertNotNull($event->acted_at);
        $this->assertNull($event->reason);

        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('Display name updated')
            ->assertSee($actor->name)
            ->assertSee('Display name: North Ada Sample')
            ->assertSee('Display name: North Ada Updated')
            ->assertDontSee('No audit events.');
    }

    public function test_episode_creation_records_one_event_with_the_new_values(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-61', 'North Ben Sample');
        $actor = $this->signInFor($company);

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-04-01',
            'episode_status' => 'Open',
        ])->assertRedirect();

        $events = AuditEvent::query()->where('company_id', $company->id)->get();
        $this->assertCount(1, $events);
        $event = $events->first();
        $episode = $employee->episodes()->first();
        $this->assertSame(AuditEvent::SUBJECT_EPISODE, $event->subject_kind);
        $this->assertSame($episode->id, $event->subject_id);
        $this->assertSame(AuditEvent::ACTION_EPISODE_CREATED, $event->action_kind);
        $this->assertSame($actor->id, $event->acting_user_id);
        $this->assertNull($event->previous_value);
        $this->assertSame([
            'joining_date' => '2026-04-01',
            'last_working_date' => null,
            'episode_status' => 'Open',
        ], $event->new_value);

        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('Employment episode created')
            ->assertSee('Joining date: 2026-04-01')
            ->assertSee('Status: Open')
            ->assertSee('Last working date: —');
    }

    public function test_a_successful_episode_update_records_the_old_and_new_values(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-62', 'North Cara Sample');
        $actor = $this->signInFor($company);

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-02-01',
            'episode_status' => 'Open',
        ])->assertRedirect();
        $episode = $employee->episodes()->first();

        $this->put('/employees/'.$employee->id.'/episodes/'.$episode->id, [
            'joining_date' => '2026-02-01',
            'episode_status' => 'separated',
            'last_working_date' => '2026-02-28',
        ])->assertRedirect();

        $update = AuditEvent::query()
            ->where('subject_id', $episode->id)
            ->where('action_kind', AuditEvent::ACTION_EPISODE_UPDATED)
            ->get();
        $this->assertCount(1, $update);
        $this->assertSame($actor->id, $update->first()->acting_user_id);
        $this->assertSame($company->id, $update->first()->company_id);
        $this->assertSame([
            'joining_date' => '2026-02-01',
            'last_working_date' => null,
            'episode_status' => 'Open',
        ], $update->first()->previous_value);
        $this->assertSame([
            'joining_date' => '2026-02-01',
            'last_working_date' => '2026-02-28',
            'episode_status' => 'separated',
        ], $update->first()->new_value);
        $this->assertSame(2, AuditEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_a_rejected_update_records_no_event(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-63', 'North Drew Sample');
        $this->signInFor($company);

        $this->put('/employees/'.$employee->id, [
            'display_name' => '',
        ])->assertSessionHasErrors('display_name');

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-03-01',
            'episode_status' => 'Open',
            'last_working_date' => '2026-03-15',
        ])->assertSessionHasErrors('last_working_date');

        $this->assertSame(0, AuditEvent::query()->where('company_id', $company->id)->count());
        $this->assertSame('North Drew Sample', $employee->refresh()->display_name);
        $this->assertSame(0, $employee->episodes()->count());
    }

    public function test_a_second_open_episode_records_no_event(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-64', 'North Erin Sample');
        $this->signInFor($company);

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-05-01',
            'episode_status' => 'Open',
        ])->assertRedirect();

        $this->post('/employees/'.$employee->id.'/episodes', [
            'joining_date' => '2026-06-01',
            'episode_status' => 'Open',
        ])->assertSessionHasErrors('episode_status');

        $events = AuditEvent::query()->where('company_id', $company->id)->get();
        $this->assertCount(1, $events);
        $this->assertSame(AuditEvent::ACTION_EPISODE_CREATED, $events->first()->action_kind);
        $this->assertSame(1, $employee->episodes()->count());
    }

    public function test_a_viewer_can_read_history_and_cannot_write_an_event(): void
    {
        $company = $this->company('Synthetic North Workshop');
        $employee = $this->employee($company, 'SYN-65', 'North Fran Sample');
        $actor = $this->signInFor($company);

        $this->put('/employees/'.$employee->id, [
            'display_name' => 'North Fran Updated',
        ])->assertRedirect();

        $this->signInFor($company, false);

        $this->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertSee('Display name updated')
            ->assertSee($actor->name)
            ->assertSee('Display name: North Fran Updated');

        $this->put('/employees/'.$employee->id, [
            'display_name' => 'North Fran Forbidden',
        ])->assertForbidden();

        $this->assertSame('North Fran Updated', $employee->refresh()->display_name);
        $this->assertSame(1, AuditEvent::query()->where('company_id', $company->id)->count());
    }

    public function test_another_company_cannot_read_the_audit_history(): void
    {
        $north = $this->company('Synthetic North Workshop');
        $south = $this->company('Synthetic South Workshop');
        $employee = $this->employee($north, 'SYN-66', 'North Glen Sample');
        $southern = $this->employee($south, 'SYN-66', 'South Glen Sample');
        $this->signInFor($north);

        $this->put('/employees/'.$employee->id, [
            'display_name' => 'North Glen Updated',
        ])->assertRedirect();

        $this->signInFor($south);

        $this->get('/employees/'.$employee->id)->assertNotFound();
        $this->get('/employees/'.$southern->id)
            ->assertOk()
            ->assertSee('No audit events.')
            ->assertDontSee('North Glen Updated');
        $this->assertSame(0, AuditEvent::query()->where('company_id', $south->id)->count());
        $this->assertSame(1, AuditEvent::query()->where('company_id', $north->id)->count());
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
