<?php

namespace Tests\Feature;

use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendanceMissingPunchRequest;
use App\Models\AttendancePayrollActionGrant;
use App\Models\AttendancePolicy;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchRecordingWriter;
use App\Models\RosterSourceDay;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\EmployeeDatabaseTestCase;

class MissingPunchRequestTest extends EmployeeDatabaseTestCase
{
    public function test_an_approved_missing_out_time_adds_a_version_and_leaves_punches_and_history(): void
    {
        $setup = $this->singlePunchDay('Harbor Missing Out');
        $before = $this->current($setup['employee']);
        $this->assertSame('A', $before->status_code);
        $punchesBefore = PunchEvent::query()->where('company_id', $setup['company']->id)->get()->map(fn (PunchEvent $event): string => $event->id.'|'.$event->event_at->toIso8601String())->all();

        $request = $this->postJson('/api/attendance/missing-punch-requests', [
            'employee_id' => $setup['employee']->id,
            'work_date' => '2026-09-13',
            'punch_side' => 'out',
            'requested_event_at' => '2026-09-13T21:00:00+06:00',
            'reason' => 'Synthetic out punch forgotten',
        ])->assertCreated();
        $this->assertSame('submitted', $request->json('request_state'));
        $this->assertSame($before->id, $request->json('result_version_id'));

        $this->signInAs($setup['reviewer'], $setup['company']);
        $decided = $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])->assertOk();

        $after = $this->current($setup['employee']);
        $this->assertSame('applied', $decided->json('request_state'));
        $this->assertSame($after->id, $decided->json('applied_version_id'));
        $this->assertSame('missing_punch', $after->generation_kind);
        $this->assertSame('P', $after->status_code);
        $this->assertFalse($after->incomplete);
        $this->assertSame($before->id, $after->previous_version_id);
        $this->assertSame($before->selected_in_event_id, $after->selected_in_event_id);
        $this->assertNull($after->selected_out_event_id);
        $this->assertSame('2026-09-13 21:00:00', $after->override_out_at->copy()->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'));
        $this->assertSame($request->json('id'), $after->override_out_request_id);
        $this->assertSame('A', $before->fresh()->status_code);
        $this->assertSame($punchesBefore, PunchEvent::query()->where('company_id', $setup['company']->id)->get()->map(fn (PunchEvent $event): string => $event->id.'|'.$event->event_at->toIso8601String())->all());

        $this->signInAs($setup['requester'], $setup['company']);
        $this->postJson('/api/attendance/day-results', ['employee_id' => $setup['employee']->id, 'work_date' => '2026-09-13'])
            ->assertOk()
            ->assertJsonPath('version_added', false);
    }

    public function test_rejection_keeps_the_current_version(): void
    {
        $setup = $this->singlePunchDay('Harbor Missing Reject');
        $before = $this->current($setup['employee']);
        $request = $this->submit($setup, 'out', '2026-09-13T18:00:00+06:00')->assertCreated();

        $this->signInAs($setup['reviewer'], $setup['company']);
        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'rejected'])
            ->assertOk()
            ->assertJsonPath('request_state', 'rejected');

        $this->assertSame($before->id, $this->current($setup['employee'])->id);
        $this->assertSame(1, AttendanceDayResultVersion::query()->where('employee_id', $setup['employee']->id)->count());
    }

    public function test_unsupported_requests_are_refused_without_writing(): void
    {
        $setup = $this->singlePunchDay('Harbor Missing Refuse');

        $this->submit($setup, 'out', '2026-09-13 21:00:00')
            ->assertStatus(422)
            ->assertJsonPath('errors.requested_event_at.0', 'The requested time needs a date, a time, and a UTC offset. A blank time is not filled from the shift.');
        $this->submit($setup, 'in', '2026-09-13T08:00:59+06:00')
            ->assertStatus(422)
            ->assertJsonPath('errors.punch_side.0', 'This side already has a punch. Replacing a recorded punch time is not an approved workflow.');
        $this->submit($setup, 'out', '2026-09-14T07:00:00+06:00')
            ->assertStatus(422)
            ->assertJsonPath('errors.requested_event_at.0', 'The requested time is outside the punch window for this day.');
        AttendancePolicy::query()->where('company_id', $setup['company']->id)->update(['missing_punch_requests' => false]);
        $this->submit($setup, 'out', '2026-09-13T21:00:00+06:00')
            ->assertStatus(422)
            ->assertJsonPath('errors.punch_side.0', 'This company has not allowed missing-punch requests.');

        $this->assertSame(0, AttendanceMissingPunchRequest::query()->where('company_id', $setup['company']->id)->count());
    }

    public function test_a_requester_holding_the_review_grant_cannot_approve_their_own_request(): void
    {
        $setup = $this->singlePunchDay('Harbor Missing Self Review');
        AttendancePayrollActionGrant::query()->insert(['user_id' => $setup['requester']->id, 'company_id' => $setup['company']->id, 'action_key' => AttendancePayrollActionGrant::REVIEW_MISSING_PUNCH, 'authorized_at' => now()]);
        $request = $this->submit($setup, 'out', '2026-09-13T21:00:00+06:00')->assertCreated();

        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])
            ->assertStatus(422)
            ->assertJsonPath('errors.request_id.0', 'The requester cannot review their own missing-punch request.');

        $this->assertSame('submitted', AttendanceMissingPunchRequest::query()->find($request->json('id'))?->request_state);
        $this->assertSame(1, AttendanceDayResultVersion::query()->where('employee_id', $setup['employee']->id)->count());
        $audit = AuditEvent::query()->where('company_id', $setup['company']->id)->where('subject_id', $request->json('id'))->where('action_kind', 'attendance_missing_punch_self_review_refused')->first();
        $this->assertInstanceOf(AuditEvent::class, $audit);
        $this->assertSame($setup['requester']->id, $audit->acting_user_id);

        $this->signInAs($setup['reviewer'], $setup['company']);
        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])->assertOk();
        $applied = AuditEvent::query()->where('company_id', $setup['company']->id)->where('subject_id', $request->json('id'))->where('action_kind', 'attendance_missing_punch_applied')->first();
        $this->assertSame($setup['reviewer']->id, $applied?->acting_user_id);
    }

    public function test_review_needs_the_review_grant_and_stays_inside_the_company(): void
    {
        $setup = $this->singlePunchDay('Harbor Missing Grant');
        $request = $this->submit($setup, 'out', '2026-09-13T21:00:00+06:00')->assertCreated();

        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])
            ->assertForbidden()
            ->assertJsonPath('action', AttendancePayrollActionGrant::REVIEW_MISSING_PUNCH);

        $other = $this->singlePunchDay('Harbor Missing Other');
        $this->signInAs($other['reviewer'], $other['company']);
        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])
            ->assertStatus(422)
            ->assertJsonPath('errors.request_id.0', 'There is no submitted missing-punch request to decide.');
        $this->assertSame('submitted', AttendanceMissingPunchRequest::query()->find($request->json('id'))?->request_state);
    }

    public function test_a_pending_request_blocks_finalization_and_a_later_recorded_punch_is_not_silently_preferred(): void
    {
        $setup = $this->singlePunchDay('Harbor Missing Period');
        $request = $this->submit($setup, 'out', '2026-09-13T21:00:00+06:00')->assertCreated();
        $period = $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-13', 'period_end' => '2026-09-13'])->assertCreated();
        AttendancePayrollActionGrant::query()->insert(['user_id' => $setup['requester']->id, 'company_id' => $setup['company']->id, 'action_key' => AttendancePayrollActionGrant::FINALIZE_PERIOD, 'authorized_at' => now()]);

        $blocked = $this->postJson('/api/attendance/periods/'.$period->json('period.id').'/finalize')->assertStatus(422);
        $this->assertContains('1 missing-punch requests are still waiting for review.', $blocked->json('errors.period_state'));

        $this->signInAs($setup['reviewer'], $setup['company']);
        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])->assertOk();
        $this->signInAs($setup['requester'], $setup['company']);
        $this->punch($setup['company'], $setup['employee'], '2026-09-13T20:30:00+06:00');
        $this->postJson('/api/attendance/day-results', ['employee_id' => $setup['employee']->id, 'work_date' => '2026-09-13'])
            ->assertStatus(422)
            ->assertJsonPath('errors.punch_side.0', 'An approved missing-punch time and a recorded out punch now both exist for this day. Which one counts is not decided.');
        $report = $this->getJson('/api/attendance/report?from=2026-09-13&to=2026-09-13')->assertOk();
        $this->assertSame([['employee_id' => $setup['employee']->id, 'work_date' => '2026-09-13', 'punch_sides' => ['out']]], $report->json('missing_punch_conflicts'));
        $conflicted = $this->postJson('/api/attendance/periods/'.$period->json('period.id').'/finalize')->assertStatus(422);
        $this->assertContains('1 days have an approved missing-punch time and a later recorded punch on the same side.', $conflicted->json('errors.period_state'));

        $this->expectException(QueryException::class);
        DB::table('attendance_missing_punch_requests')->where('id', $request->json('id'))->update(['requested_event_at' => now()]);
    }

    /**
     * @param  array{employee: Employee}  $setup
     */
    private function submit(array $setup, string $side, string $at): TestResponse
    {
        return $this->postJson('/api/attendance/missing-punch-requests', [
            'employee_id' => $setup['employee']->id,
            'work_date' => '2026-09-13',
            'punch_side' => $side,
            'requested_event_at' => $at,
            'reason' => 'Synthetic missing punch',
        ]);
    }

    /**
     * A synthetic roster day shaped like the legacy shift 16 calendar row with one
     * in punch, the shape of the 2026-09-13 case. It is not legacy data.
     *
     * @return array{company: Company, employee: Employee, requester: User, reviewer: User}
     */
    private function singlePunchDay(string $name): array
    {
        $company = Company::query()->create(['company_name' => $name, 'status' => 'Active']);
        $requester = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert(['user_id' => $requester->id, 'company_id' => $company->id, 'authorized_at' => now()]);
        $reviewer = User::query()->create(['name' => 'Synthetic Reviewer', 'email' => 'reviewer-'.strtolower((string) Str::ulid()).'@example.test', 'password' => 'synthetic-passphrase-1']);
        CompanyMembership::query()->create(['user_id' => $reviewer->id, 'company_id' => $company->id, 'status' => 'active', 'can_manage_employees' => false]);
        AttendancePayrollActionGrant::query()->insert(['user_id' => $reviewer->id, 'company_id' => $company->id, 'action_key' => AttendancePayrollActionGrant::REVIEW_MISSING_PUNCH, 'authorized_at' => now()]);
        AttendancePolicy::query()->create([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'roster',
            'punch_selection' => 'margin_first_and_last',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'correction_fields' => '',
            'working_day_code' => 'W',
            'non_working_day_code' => 'H',
            'non_working_status_code' => 'H',
            'non_working_attended_status_code' => 'HP',
            'missing_punch_requests' => true,
        ]);
        $employee = Employee::query()->create(['company_id' => $company->id, 'employee_code' => 'SYN-MISS-1', 'display_name' => 'Synthetic Missing']);
        $shift = Shift::query()->create(['company_id' => $company->id, 'name' => 'Synthetic early day', 'source_system' => 'synthetic_attendance_test']);
        ShiftVersion::query()->create([
            'company_id' => $company->id,
            'shift_id' => $shift->id,
            'start_time' => '08:00:59',
            'end_time' => '17:00:00',
            'crosses_midnight' => false,
            'effective_from' => '2026-01-01',
            'source_system' => 'synthetic_attendance_test',
        ]);
        ShiftAssignmentCandidate::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'candidate_kind' => 'roster',
            'roster_work_date' => '2026-09-13',
            'source_system' => 'synthetic_attendance_test',
        ]);
        RosterSourceDay::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => '2026-09-13',
            'source_day_code' => 'W',
            'always_present' => false,
            'source_roster_flag' => 'No',
            'window_starts_at' => Carbon::parse('2026-09-13T06:30:00+06:00')->utc(),
            'window_ends_at' => Carbon::parse('2026-09-14T06:00:00+06:00')->utc(),
            'shift_starts_at' => Carbon::parse('2026-09-13T08:00:59+06:00')->utc(),
            'shift_ends_at' => Carbon::parse('2026-09-13T17:00:00+06:00')->utc(),
            'absent_after_at' => Carbon::parse('2026-09-13T10:00:00+06:00')->utc(),
            'source_system' => 'synthetic_attendance_test',
            'source_key' => $name,
            'source_catalog_key' => 'synthetic',
            'source_employee_key' => 'SYN-MISS-1',
            'source_shift_key' => 'synthetic',
        ]);
        $this->punch($company, $employee, '2026-09-13T07:41:57+06:00');
        $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => '2026-09-13'])->assertCreated();

        return ['company' => $company, 'employee' => $employee, 'requester' => $requester, 'reviewer' => $reviewer];
    }

    private function signInAs(User $user, Company $company): void
    {
        $this->actingAs($user);
        session([CompanyContext::SESSION_KEY => $company->id]);
    }

    private function punch(Company $company, Employee $employee, string $eventAt): PunchEvent
    {
        return app(PunchRecordingRegistry::class)->record($company->id, [
            'employee_id' => $employee->id,
            'identity_value' => $employee->employee_code,
            'source_name' => 'synthetic attendance test',
            'event_at' => $eventAt,
        ]);
    }

    private function current(Employee $employee): AttendanceDayResultVersion
    {
        $pointer = AttendanceDayResult::query()->where('employee_id', $employee->id)->whereDate('work_date', '2026-09-13')->first();
        $version = $pointer === null ? null : AttendanceDayResultVersion::query()->find($pointer->current_version_id);
        $this->assertInstanceOf(AttendanceDayResultVersion::class, $version);

        return $version;
    }
}
