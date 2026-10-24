<?php

namespace Tests\Feature;

use App\Models\AttendancePayrollActionGrant;
use App\Models\AttendancePeriodEvent;
use App\Models\AttendancePolicy;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchRecordingWriter;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Str;
use Tests\EmployeeDatabaseTestCase;

class AttendancePeriodAuthorizationTest extends EmployeeDatabaseTestCase
{
    public function test_the_punch_writer_grant_alone_cannot_finalize_reopen_or_hand_off(): void
    {
        $setup = $this->processedPeriod('Harbor Grant None', []);

        $this->postJson('/api/attendance/periods/'.$setup['period'].'/finalize')
            ->assertForbidden()
            ->assertJsonPath('message', 'This action is not authorized for the active company.')
            ->assertJsonPath('action', AttendancePayrollActionGrant::FINALIZE_PERIOD);
        $this->postJson('/api/attendance/periods/'.$setup['period'].'/reopen', ['reason' => 'Synthetic'])->assertForbidden();
        $this->postJson('/api/attendance/periods/'.$setup['period'].'/payroll-handoff')->assertForbidden();
        $this->assertSame(['opened'], AttendancePeriodEvent::query()->where('attendance_period_id', $setup['period'])->pluck('event_kind')->all());
    }

    public function test_each_period_action_needs_its_own_grant(): void
    {
        $setup = $this->processedPeriod('Harbor Grant Finalize', [AttendancePayrollActionGrant::FINALIZE_PERIOD]);

        $this->postJson('/api/attendance/periods/'.$setup['period'].'/finalize')->assertOk();
        $this->postJson('/api/attendance/periods/'.$setup['period'].'/payroll-handoff')
            ->assertForbidden()
            ->assertJsonPath('action', AttendancePayrollActionGrant::HANDOFF_PERIOD);
        $this->postJson('/api/attendance/periods/'.$setup['period'].'/reopen', ['reason' => 'Synthetic'])
            ->assertForbidden()
            ->assertJsonPath('action', AttendancePayrollActionGrant::REOPEN_PERIOD);
    }

    public function test_grants_in_one_company_do_not_reach_another_companys_period(): void
    {
        $other = $this->processedPeriod('Harbor Grant Other', [AttendancePayrollActionGrant::FINALIZE_PERIOD]);
        $own = $this->processedPeriod('Harbor Grant Own', [AttendancePayrollActionGrant::FINALIZE_PERIOD, AttendancePayrollActionGrant::REOPEN_PERIOD, AttendancePayrollActionGrant::HANDOFF_PERIOD]);

        $this->postJson('/api/attendance/periods/'.$other['period'].'/finalize')->assertNotFound();
        $this->postJson('/api/attendance/periods/'.$other['period'].'/reopen', ['reason' => 'Synthetic'])->assertNotFound();
        $this->postJson('/api/attendance/periods/'.$other['period'].'/payroll-handoff')->assertNotFound();

        CompanyMembership::query()->create([
            'user_id' => $own['user']->id,
            'company_id' => $other['company']->id,
            'status' => 'active',
            'can_manage_employees' => false,
        ]);
        session([CompanyContext::SESSION_KEY => $other['company']->id]);
        $this->postJson('/api/attendance/periods/'.$other['period'].'/finalize')
            ->assertForbidden()
            ->assertJsonPath('action', AttendancePayrollActionGrant::FINALIZE_PERIOD);
        $this->assertSame(['opened'], AttendancePeriodEvent::query()->where('attendance_period_id', $other['period'])->pluck('event_kind')->all());
    }

    public function test_an_inactive_membership_loses_the_period_grant(): void
    {
        $setup = $this->processedPeriod('Harbor Grant Inactive', [AttendancePayrollActionGrant::FINALIZE_PERIOD]);
        CompanyMembership::query()->where('user_id', $setup['user']->id)->where('company_id', $setup['company']->id)->update(['status' => 'inactive']);

        $this->postJson('/api/attendance/periods/'.$setup['period'].'/finalize')->assertForbidden();
    }

    public function test_reopening_records_the_actor_time_and_reason(): void
    {
        $setup = $this->processedPeriod('Harbor Grant Reopen', [AttendancePayrollActionGrant::FINALIZE_PERIOD, AttendancePayrollActionGrant::REOPEN_PERIOD]);
        $this->postJson('/api/attendance/periods/'.$setup['period'].'/finalize')->assertOk();

        $this->postJson('/api/attendance/periods/'.$setup['period'].'/reopen', ['reason' => 'Synthetic missing punch found'])->assertOk();

        $event = AttendancePeriodEvent::query()->where('attendance_period_id', $setup['period'])->where('event_kind', 'reopened')->first();
        $this->assertInstanceOf(AttendancePeriodEvent::class, $event);
        $this->assertSame($setup['user']->id, $event->acting_user_id);
        $this->assertSame('Synthetic missing punch found', $event->reason);
        $this->assertNotNull($event->occurred_at);
        $audit = AuditEvent::query()->where('company_id', $setup['company']->id)->where('subject_id', $setup['period'])->where('action_kind', 'attendance_period_reopened')->first();
        $this->assertInstanceOf(AuditEvent::class, $audit);
        $this->assertSame($setup['user']->id, $audit->acting_user_id);
        $this->assertSame('Synthetic missing punch found', $audit->new_value['reason']);
    }

    public function test_the_grant_command_needs_an_active_membership_and_a_known_action(): void
    {
        $company = Company::query()->create(['company_name' => 'Harbor Grant Command', 'status' => 'Active']);
        $member = $this->signInFor($company, false);
        $stranger = User::query()->create(['name' => 'Synthetic Stranger', 'email' => 'stranger-'.strtolower((string) Str::ulid()).'@example.test', 'password' => 'synthetic-passphrase-1']);

        $this->artisan('hrms:authorize-attendance-payroll-action', ['--email' => $member->email, '--company' => $company->id, '--action' => 'approve_everything'])->assertFailed();
        $this->artisan('hrms:authorize-attendance-payroll-action', ['--email' => $stranger->email, '--company' => $company->id, '--action' => AttendancePayrollActionGrant::FINALIZE_PERIOD])->assertFailed();
        $this->artisan('hrms:authorize-attendance-payroll-action', ['--email' => $member->email, '--company' => $company->id, '--action' => AttendancePayrollActionGrant::FINALIZE_PERIOD])->assertSuccessful();

        $this->assertSame([AttendancePayrollActionGrant::FINALIZE_PERIOD], AttendancePayrollActionGrant::query()->where('company_id', $company->id)->pluck('action_key')->all());
    }

    /**
     * @param  list<string>  $actions
     * @return array{company: Company, user: User, period: string}
     */
    private function processedPeriod(string $name, array $actions): array
    {
        $company = Company::query()->create(['company_name' => $name, 'status' => 'Active']);
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert(['user_id' => $user->id, 'company_id' => $company->id, 'authorized_at' => now()]);
        foreach ($actions as $action) {
            AttendancePayrollActionGrant::query()->insert(['user_id' => $user->id, 'company_id' => $company->id, 'action_key' => $action, 'authorized_at' => now()]);
        }
        AttendancePolicy::query()->create([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'default',
            'punch_selection' => 'first_and_last',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'correction_fields' => '',
        ]);
        $employee = Employee::query()->create(['company_id' => $company->id, 'employee_code' => 'SYN-GRANT-1', 'display_name' => 'Synthetic Grant']);
        $shift = Shift::query()->create(['company_id' => $company->id, 'name' => 'Day', 'source_system' => 'synthetic_attendance_test']);
        ShiftVersion::query()->create([
            'company_id' => $company->id,
            'shift_id' => $shift->id,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'crosses_midnight' => false,
            'effective_from' => '2026-01-01',
            'source_system' => 'synthetic_attendance_test',
        ]);
        ShiftAssignmentCandidate::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'candidate_kind' => 'default',
            'source_system' => 'synthetic_attendance_test',
        ]);
        foreach (['2026-09-02T09:00:00+06:00', '2026-09-02T17:00:00+06:00'] as $eventAt) {
            app(PunchRecordingRegistry::class)->record($company->id, [
                'employee_id' => $employee->id,
                'identity_value' => 'SYN-GRANT-1',
                'source_name' => 'synthetic attendance test',
                'event_at' => $eventAt,
            ]);
        }
        $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => '2026-09-02'])->assertCreated();
        $period = $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-02'])->assertCreated();

        return ['company' => $company, 'user' => $user, 'period' => $period->json('period.id')];
    }
}
