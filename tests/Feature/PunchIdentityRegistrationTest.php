<?php

namespace Tests\Feature;

use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchIdentityOverlap;
use App\Models\PunchRecordingWriter;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\EmployeeDatabaseTestCase;

class PunchIdentityRegistrationTest extends EmployeeDatabaseTestCase
{
    public function test_a_card_is_registered_on_an_existing_employee_with_audit_history(): void
    {
        $company = $this->company('Harbor Card Register');
        $user = $this->signInAsWriter($company);
        $employee = $this->employee($company, 'CRD-1');
        $employeesBefore = Employee::query()->where('company_id', $company->id)->count();

        $created = $this->postJson('/api/punch-identities', [
            'employee_id' => $employee->id,
            'identity_value' => ' 5512345 ',
            'effective_from' => '2026-01-01T00:00:00+06:00',
            'reference' => 'Synthetic card map row 17',
        ])->assertCreated()
            ->assertJsonPath('identity_value', '5512345')
            ->assertJsonPath('employee_code', 'CRD-1')
            ->assertJsonPath('effective_from', '2025-12-31T18:00:00+00:00')
            ->assertJsonPath('effective_to', null)
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('registered_in_another_company', false);

        $detail = $this->getJson('/api/punch-identities/'.$created->json('id'))->assertOk();
        $this->assertSame(0, $detail->json('punch_event_count'));
        $this->assertSame('punch_identity_registered', $detail->json('history.0.action_kind'));
        $this->assertSame($user->id, $detail->json('history.0.acting_user_id'));
        $this->assertSame('Synthetic card map row 17', $detail->json('history.0.reason'));
        $this->assertSame('5512345', $detail->json('history.0.new_value.identity_value'));
        $this->assertNotNull($detail->json('history.0.acted_at'));
        $this->assertSame($employeesBefore, Employee::query()->where('company_id', $company->id)->count());
        $this->assertSame(0, PunchEvent::query()->where('company_id', $company->id)->count());
        $this->assertSame('5512345', $this->getJson('/api/punch-identities?q=5512')->json('data.0.identity_value'));
        $this->assertSame([], $this->getJson('/api/punch-identities?q=nothing-like-this')->json('data'));
    }

    public function test_employee_and_identity_must_belong_to_the_active_company(): void
    {
        $other = $this->company('Harbor Card Other');
        $foreignEmployee = $this->employee($other, 'CRD-FOREIGN');
        $this->signInAsWriter($other);
        $foreignIdentity = $this->postJson('/api/punch-identities', ['employee_id' => $foreignEmployee->id, 'identity_value' => 'F-1', 'effective_from' => '2026-01-01T00:00:00+06:00'])->assertCreated()->json('id');

        $own = $this->company('Harbor Card Own');
        $this->signInAsWriter($own);
        $this->postJson('/api/punch-identities', ['employee_id' => $foreignEmployee->id, 'identity_value' => 'F-2', 'effective_from' => '2026-01-01T00:00:00+06:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.employee_id.0', 'The employee must belong to the active company.');
        $this->postJson('/api/punch-identities', ['employee_id' => $foreignEmployee->id, 'company_id' => $other->id, 'identity_value' => 'F-2', 'effective_from' => '2026-01-01T00:00:00+06:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.company_id.0', 'The company is taken from the signed-in company context, not from the form.');
        $this->getJson('/api/punch-identities/'.$foreignIdentity)->assertNotFound();
        $this->postJson('/api/punch-identities/'.$foreignIdentity.'/end', ['effective_to' => '2026-06-01T00:00:00+06:00', 'reason' => 'Synthetic'])
            ->assertStatus(422)
            ->assertJsonPath('errors.identity_id.0', 'The card mapping is not in this company.');
        $this->assertSame([], $this->getJson('/api/punch-identities')->json('data'));
        $this->assertNull(PunchIdentity::query()->find($foreignIdentity)?->effective_to);
        $this->assertSame(1, PunchIdentity::query()->whereIn('company_id', [$own->id, $other->id])->count());
    }

    public function test_overlapping_registrations_are_refused_and_sequential_ones_are_allowed(): void
    {
        $company = $this->company('Harbor Card Overlap');
        $this->signInAsWriter($company);
        $first = $this->employee($company, 'CRD-A');
        $second = $this->employee($company, 'CRD-B');
        $original = $this->postJson('/api/punch-identities', ['employee_id' => $first->id, 'identity_value' => 'CARD-77', 'effective_from' => '2026-01-01T00:00:00+06:00'])->assertCreated();

        $this->postJson('/api/punch-identities', ['employee_id' => $first->id, 'identity_value' => 'CARD-77', 'effective_from' => '2026-03-01T00:00:00+06:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.identity_value.0', 'This card is already registered for this employee in an overlapping period.');
        $this->postJson('/api/punch-identities', ['employee_id' => $second->id, 'identity_value' => 'CARD-77', 'effective_from' => '2026-03-01T00:00:00+06:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.identity_value.0', 'This card is already registered to employee CRD-A in this company for part of that period. End that mapping first, or record a documented overlap.');
        $this->postJson('/api/punch-identities', ['employee_id' => $second->id, 'identity_value' => 'CARD-77', 'effective_from' => '2026-03-01 00:00:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.effective_from.0', 'The time needs a date, a time, and a UTC offset.');

        $this->postJson('/api/punch-identities/'.$original->json('id').'/end', ['effective_to' => '2026-06-01T00:00:00+06:00', 'reason' => ' '])->assertStatus(422);
        $ended = $this->postJson('/api/punch-identities/'.$original->json('id').'/end', ['effective_to' => '2026-06-01T00:00:00+06:00', 'reason' => 'Synthetic card reissued'])
            ->assertOk()
            ->assertJsonPath('effective_to', '2026-05-31T18:00:00+00:00')
            ->assertJsonPath('status', 'ended');
        $this->postJson('/api/punch-identities', ['employee_id' => $second->id, 'identity_value' => 'CARD-77', 'effective_from' => '2026-05-31T23:00:00+06:00'])->assertStatus(422);
        $this->postJson('/api/punch-identities', ['employee_id' => $second->id, 'identity_value' => 'CARD-77', 'effective_from' => '2026-06-01T00:00:00+06:00'])
            ->assertCreated()
            ->assertJsonPath('employee_code', 'CRD-B');

        $history = $this->getJson('/api/punch-identities/'.$ended->json('id'))->assertOk();
        $this->assertSame(['punch_identity_registered', 'punch_identity_ended'], array_column($history->json('history'), 'action_kind'));
        $this->assertSame('Synthetic card reissued', $history->json('history.1.reason'));
        $this->assertNull($history->json('history.1.previous_value.effective_to'));
        $this->assertSame([], $this->getJson('/api/punch-identities/conflicts')->json('in_company'));
    }

    public function test_a_documented_overlap_permits_a_shared_period_and_is_listed(): void
    {
        $company = $this->company('Harbor Card Documented');
        $this->signInAsWriter($company);
        $first = $this->employee($company, 'CRD-D1');
        $second = $this->employee($company, 'CRD-D2');
        $this->postJson('/api/punch-identities', ['employee_id' => $first->id, 'identity_value' => 'CARD-88', 'effective_from' => '2026-01-01T00:00:00+06:00', 'effective_to' => '2026-02-01T00:00:00+06:00'])->assertCreated();
        PunchIdentityOverlap::query()->create([
            'company_id' => $company->id,
            'identity_value' => 'CARD-88',
            'effective_from' => '2026-01-10T00:00:00Z',
            'effective_to' => '2026-02-01T00:00:00Z',
            'reason' => 'Synthetic documented overlap',
        ]);

        $this->postJson('/api/punch-identities', ['employee_id' => $second->id, 'identity_value' => 'CARD-88', 'effective_from' => '2026-01-15T00:00:00+06:00', 'effective_to' => '2026-01-20T00:00:00+06:00'])->assertCreated();
        $this->postJson('/api/punch-identities', ['employee_id' => $second->id, 'identity_value' => 'CARD-88', 'effective_from' => '2026-01-05T00:00:00+06:00', 'effective_to' => '2026-01-08T00:00:00+06:00'])->assertStatus(422);

        $conflicts = $this->getJson('/api/punch-identities/conflicts')->assertOk();
        $this->assertCount(1, $conflicts->json('in_company'));
        $this->assertTrue($conflicts->json('in_company.0.documented'));
        $this->assertSame(['CRD-D1', 'CRD-D2'], $conflicts->json('in_company.0.employee_codes'));
        $this->assertSame('Synthetic documented overlap', $conflicts->json('documented_overlaps.0.reason'));
    }

    public function test_a_mapping_cannot_end_before_its_recorded_punches(): void
    {
        $company = $this->company('Harbor Card Punches');
        $this->signInAsWriter($company);
        $employee = $this->employee($company, 'CRD-P');
        $identity = $this->postJson('/api/punch-identities', ['employee_id' => $employee->id, 'identity_value' => 'CARD-P', 'effective_from' => '2026-01-01T00:00:00+06:00'])->assertCreated()->json('id');
        app(PunchRecordingRegistry::class)->record($company->id, ['employee_id' => $employee->id, 'identity_value' => 'CARD-P', 'source_name' => 'synthetic attendance test', 'event_at' => '2026-03-02T09:00:00+06:00']);

        $this->postJson('/api/punch-identities/'.$identity.'/end', ['effective_to' => '2026-03-01T00:00:00+06:00', 'reason' => 'Synthetic early end'])
            ->assertStatus(422)
            ->assertJsonPath('errors.effective_to.0', '1 recorded punches for this mapping fall at or after that time. The end cannot move before them.');
        $this->assertNull(PunchIdentity::query()->find($identity)?->effective_to);
        $this->assertSame(1, $this->getJson('/api/punch-identities/'.$identity)->json('punch_event_count'));
    }

    public function test_identity_screens_need_a_session_and_the_writer_grant(): void
    {
        $company = $this->company('Harbor Card Gate');
        $this->getJson('/api/punch-identities')->assertUnauthorized();
        $this->signInFor($company, true);
        $this->getJson('/api/punch-identities')->assertForbidden();
        $this->postJson('/api/punch-identities', [])->assertForbidden();
        $this->getJson('/api/punch-identities/conflicts')->assertForbidden();
    }

    public function test_the_preview_resolves_a_card_only_after_registration_and_flags_another_company(): void
    {
        config([
            'database.connections.device_log_fixture' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false],
            'attendance.device_log.connection' => 'device_log_fixture',
        ]);
        DB::purge('device_log_fixture');
        Schema::connection('device_log_fixture')->create('all_att_log', function ($table): void {
            $table->integer('id')->primary();
            $table->string('date');
            $table->string('emp_code');
            $table->integer('balid')->nullable();
            $table->string('area')->nullable();
        });
        DB::connection('device_log_fixture')->table('all_att_log')->insert([
            ['id' => 1, 'date' => '2026-09-02 08:10:00', 'emp_code' => 'CARD-SRC', 'area' => null],
        ]);
        $sourceBefore = DB::connection('device_log_fixture')->table('all_att_log')->get()->toArray();
        $company = $this->company('Harbor Card Preview');
        $this->signInAsWriter($company);
        $employee = $this->employee($company, 'CRD-SRC');

        $before = $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->assertOk();
        $this->assertSame('unmapped', $before->json('rows.0.outcome'));
        $this->assertNull($before->json('rows.0.employee_code'));

        $this->postJson('/api/punch-identities', ['employee_id' => $employee->id, 'identity_value' => 'CARD-SRC', 'effective_from' => '2026-01-01T00:00:00+06:00'])->assertCreated();
        $after = $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->assertOk();
        $this->assertSame('importable', $after->json('rows.0.outcome'));
        $this->assertSame('CRD-SRC', $after->json('rows.0.employee_code'));

        $other = $this->company('Harbor Card Preview Other');
        $this->signInAsWriter($other);
        $otherEmployee = $this->employee($other, 'CRD-SRC-OTHER');
        $this->postJson('/api/punch-identities', ['employee_id' => $otherEmployee->id, 'identity_value' => 'CARD-SRC', 'effective_from' => '2026-01-01T00:00:00+06:00'])
            ->assertCreated()
            ->assertJsonPath('registered_in_another_company', true);
        $this->assertSame([[
            'identity_value' => 'CARD-SRC',
            'identity_ids' => [PunchIdentity::query()->where('company_id', $other->id)->value('id')],
            'shared_periods' => [['from' => '2025-12-31T18:00:00+00:00', 'to' => null]],
        ]], $this->getJson('/api/punch-identities/conflicts')->json('other_company'));
        $this->assertSame('ambiguous', $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->json('rows.0.outcome'));

        $otherIdentityId = PunchIdentity::query()->where('company_id', $other->id)->value('id');
        $this->postJson('/api/punch-identities/'.$otherIdentityId.'/end', ['effective_to' => '2026-06-01T00:00:00+06:00', 'reason' => 'Synthetic card returned'])->assertOk();
        $this->assertSame(
            [['from' => '2025-12-31T18:00:00+00:00', 'to' => '2026-05-31T18:00:00+00:00']],
            $this->getJson('/api/punch-identities/conflicts')->json('other_company.0.shared_periods'),
        );
        $this->assertSame('unmapped', $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->json('rows.0.outcome'));

        $this->assertSame(0, PunchEvent::query()->whereIn('company_id', [$company->id, $other->id])->count());
        $this->assertEquals($sourceBefore, DB::connection('device_log_fixture')->table('all_att_log')->get()->toArray());
    }

    public function test_the_card_identity_screen_is_routed_and_stays_inside_the_registration_boundary(): void
    {
        $app = (string) file_get_contents(base_path('frontend/src/App.tsx'));
        $dashboard = (string) file_get_contents(base_path('frontend/src/pages/RealSessionAttendanceDashboardPage.tsx'));
        $page = (string) file_get_contents(base_path('frontend/src/pages/RealSessionPunchIdentitiesPage.tsx'));
        $client = (string) file_get_contents(base_path('frontend/src/lib/attendanceWorkflowApi.ts'));

        $this->assertStringContainsString('path="/session/attendance/identities"', $app);
        $this->assertStringContainsString('to="/session/attendance/identities"', $dashboard);
        $this->assertStringContainsString('fetchEmployeeDirectory', $page);
        $this->assertStringContainsString('Select employee', $page);
        $this->assertStringContainsString('End mapping', $page);
        $this->assertStringContainsString("'/api/punch-identities'", $client);
        $this->assertStringNotContainsString("method: 'DELETE'", $page.$client);
        $this->assertStringNotContainsString('/api/punch-events', $page);
    }

    private function company(string $name): Company
    {
        $company = Company::query()->create(['company_name' => $name, 'status' => 'Active']);
        AttendancePolicy::query()->create([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'default',
            'punch_selection' => 'first_and_last',
            'correction_fields' => '',
        ]);

        return $company;
    }

    private function employee(Company $company, string $code): Employee
    {
        return Employee::query()->create(['company_id' => $company->id, 'employee_code' => $code, 'display_name' => 'Synthetic '.$code]);
    }

    private function signInAsWriter(Company $company): User
    {
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert(['user_id' => $user->id, 'company_id' => $company->id, 'authorized_at' => now()]);

        return $user;
    }
}
