<?php

namespace Tests\Feature;

use App\Attendance\AttendanceDayProcessor;
use App\Attendance\LegacyShiftWindow;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceCorrectionValue;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchRecordingWriter;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Facades\DB;
use Tests\EmployeeDatabaseTestCase;

class AttendanceWorkflowTest extends EmployeeDatabaseTestCase
{
    public function test_attendance_routes_require_the_punch_recording_writer(): void
    {
        $company = $this->company('Harbor Attendance Gate');
        $this->signInFor($company, false);

        $this->getJson('/api/attendance/day-results')->assertForbidden()
            ->assertJsonPath('message', 'Punch recording is not authorized. No writer has been named.');
        $this->postJson('/api/attendance/day-results', [])->assertForbidden();
        $this->postJson('/api/attendance/corrections', [])->assertForbidden();
    }

    public function test_processing_api_records_a_complete_late_day_and_lists_only_the_active_company(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Late', [
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
        ]);
        $other = $this->preparedCompany('Harbor Attendance Other', []);
        $employee = $this->employee($company['company'], 'SYN-WF-LATE', 'Synthetic Late Day');
        $otherEmployee = $this->employee($other['company'], 'SYN-WF-OTHER', 'Synthetic Other Day');
        $shift = $this->shift($company['company'], 'Corporate shape', '09:00:59', '17:30:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $this->punch($company['company'], $employee, '2026-09-03T09:10:59+06:00');
        $this->punch($company['company'], $employee, '2026-09-03T17:40:00+06:00');
        $otherShift = $this->shift($other['company'], 'Other day', '09:00:00', '17:00:00', false);
        $this->candidate($other['company'], $otherEmployee, $otherShift, 'default', null);
        app(AttendanceDayProcessor::class)->calculate($other['company']->id, $otherEmployee->id, '2026-09-03');
        $this->actingAs($company['user']);
        $this->withSession([CompanyContext::SESSION_KEY => $company['company']->id]);

        $created = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-03',
        ])->assertCreated()
            ->assertJsonPath('versions.0.status_code', 'P')
            ->assertJsonPath('versions.0.incomplete', false)
            ->assertJsonPath('versions.0.late_seconds', 600)
            ->assertJsonPath('versions.0.previous_version_id', null);

        $this->getJson('/api/attendance/day-results')
            ->assertOk()
            ->assertJsonPath('company_name', 'Harbor Attendance Late')
            ->assertJsonPath('data.0.employee_id', $employee->id)
            ->assertJsonPath('data.0.version_count', 1)
            ->assertJsonCount(1, 'data');
        $this->getJson('/api/attendance/day-results/'.$employee->id.'/2026-09-03')
            ->assertOk()
            ->assertJsonPath('current_version_id', $created->json('current_version_id'));
        $this->getJson('/api/attendance/day-results/'.$otherEmployee->id.'/2026-09-03')->assertNotFound();
    }

    public function test_a_missing_punch_is_incomplete_and_extra_punches_keep_first_and_last(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Punches', [
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
        ]);
        $incompleteEmployee = $this->employee($company['company'], 'SYN-WF-MISS', 'Synthetic Missing Punch');
        $multipleEmployee = $this->employee($company['company'], 'SYN-WF-MULTI', 'Synthetic Multiple Punches');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company['company'], $incompleteEmployee, $shift, 'default', null);
        $this->candidate($company['company'], $multipleEmployee, $shift, 'default', null);
        $this->punch($company['company'], $incompleteEmployee, '2026-09-13T08:00:00+06:00');
        $first = $this->punch($company['company'], $multipleEmployee, '2026-09-02T08:17:41+06:00');
        $middle = $this->punch($company['company'], $multipleEmployee, '2026-09-02T12:00:00+06:00');
        $last = $this->punch($company['company'], $multipleEmployee, '2026-09-02T17:34:36+06:00');

        $missing = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $incompleteEmployee->id,
            'work_date' => '2026-09-13',
        ])->assertCreated();
        $multiple = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $multipleEmployee->id,
            'work_date' => '2026-09-02',
        ])->assertCreated();

        $this->assertTrue($missing->json('versions.0.incomplete'));
        $this->assertSame('A', $missing->json('versions.0.status_code'));
        $this->assertNull($missing->json('versions.0.selected_out_event_id'));
        $this->assertSame($first->id, $multiple->json('versions.0.selected_in_event_id'));
        $this->assertSame($last->id, $multiple->json('versions.0.selected_out_event_id'));
        $this->assertNotSame($middle->id, $multiple->json('versions.0.selected_in_event_id'));
        $this->assertNotSame($middle->id, $multiple->json('versions.0.selected_out_event_id'));
        $this->assertSame(3, PunchEvent::query()->where('company_id', $company['company']->id)->whereHas('identity', function ($query) use ($multipleEmployee): void {
            $query->where('employee_id', $multipleEmployee->id);
        })->count());
    }

    public function test_an_overnight_shift_keeps_events_inside_the_existing_span(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Night', [
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'present_status_code' => 'P',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-NIGHT', 'Synthetic Overnight');
        $shift = $this->shift($company['company'], 'Roster N shape', '22:30:59', '06:30:00', true);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $in = $this->punch($company['company'], $employee, '2026-09-04T22:30:59+06:00');
        $out = $this->punch($company['company'], $employee, '2026-09-05T06:00:00+06:00');
        $outside = $this->punch($company['company'], $employee, '2026-09-05T12:00:00+06:00');

        $response = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-04',
        ])->assertCreated();

        $this->assertFalse($response->json('versions.0.incomplete'));
        $this->assertSame(0, $response->json('versions.0.late_seconds'));
        $this->assertSame($in->id, $response->json('versions.0.selected_in_event_id'));
        $this->assertSame($out->id, $response->json('versions.0.selected_out_event_id'));
        $this->assertNotSame($outside->id, $response->json('versions.0.selected_out_event_id'));
    }

    public function test_recalculation_adds_a_version_without_changing_the_prior_version_or_raw_punches(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Recalc', [
            'present_status_code' => 'P',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-RECALC', 'Synthetic Recalculation');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $in = $this->punch($company['company'], $employee, '2026-09-02T08:17:41+06:00');
        $out = $this->punch($company['company'], $employee, '2026-09-02T17:34:36+06:00');
        $originalIn = $in->event_at?->toIso8601String();
        $originalOut = $out->event_at?->toIso8601String();

        $first = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
        ])->assertCreated();
        $firstId = $first->json('versions.0.id');
        $this->punch($company['company'], $employee, '2026-09-02T18:00:00+06:00');
        $second = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
        ])->assertCreated();

        $storedFirst = AttendanceDayResultVersion::query()->find($firstId);
        $this->assertInstanceOf(AttendanceDayResultVersion::class, $storedFirst);
        $this->assertSame($in->id, $storedFirst->selected_in_event_id);
        $this->assertSame($out->id, $storedFirst->selected_out_event_id);
        $this->assertNull($storedFirst->previous_version_id);
        $this->assertSame($firstId, $second->json('versions.0.id'));
        $this->assertSame($firstId, $second->json('versions.1.previous_version_id'));
        $this->assertSame($second->json('versions.1.id'), $second->json('current_version_id'));
        $this->assertSame(2, AttendanceDayResultVersion::query()->where('employee_id', $employee->id)->count());
        $in->refresh();
        $out->refresh();
        $this->assertSame($originalIn, $in->event_at?->toIso8601String());
        $this->assertSame($originalOut, $out->event_at?->toIso8601String());
        $secondVersion = AttendanceDayResultVersion::query()->find($second->json('current_version_id'));
        $this->assertInstanceOf(AttendanceDayResultVersion::class, $secondVersion);
        $this->assertTrue($secondVersion->calculated_at->greaterThanOrEqualTo($storedFirst->calculated_at));
    }

    public function test_repeat_processing_keeps_version_time_when_the_database_session_is_not_utc(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Clock', [
            'present_status_code' => 'P',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-CLOCK', 'Synthetic Clock');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $this->punch($company['company'], $employee, '2026-09-02T09:00:00+06:00');
        $this->punch($company['company'], $employee, '2026-09-02T17:00:00+06:00');
        DB::statement("SET TIME ZONE 'Asia/Dhaka'");
        try {
            $first = app(AttendanceDayProcessor::class)->calculate($company['company']->id, $employee->id, '2026-09-02');
            $this->punch($company['company'], $employee, '2026-09-02T18:00:00+06:00');
            $second = app(AttendanceDayProcessor::class)->calculate($company['company']->id, $employee->id, '2026-09-02');
            $first->refresh();
            $second->refresh();
            $this->assertTrue($second->calculated_at->greaterThanOrEqualTo($first->calculated_at));
            $this->assertSame($first->id, $second->previous_version_id);
        } finally {
            DB::statement("SET TIME ZONE 'UTC'");
        }
    }

    public function test_repeat_processing_does_not_store_a_version_when_the_result_is_unchanged(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Repeat', [
            'present_status_code' => 'P',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-REPEAT', 'Synthetic Repeat');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $this->punch($company['company'], $employee, '2026-09-02T09:00:00+06:00');
        $this->punch($company['company'], $employee, '2026-09-02T17:00:00+06:00');

        $first = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
        ])->assertCreated();
        $second = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
        ])->assertOk();

        $this->assertTrue($first->json('version_added'));
        $this->assertFalse($second->json('version_added'));
        $this->assertSame($first->json('current_version_id'), $second->json('current_version_id'));
        $this->assertCount(1, $second->json('versions'));
        $this->assertSame(1, AttendanceDayResultVersion::query()->where('employee_id', $employee->id)->count());
    }

    public function test_version_history_follows_the_chain_when_the_clock_is_out_of_order(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Chain', [
            'present_status_code' => 'P',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-CHAIN', 'Synthetic Chain');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $in = $this->punch($company['company'], $employee, '2026-09-02T09:00:00+06:00');
        $out = $this->punch($company['company'], $employee, '2026-09-02T17:00:00+06:00');
        $first = app(AttendanceDayProcessor::class)->calculate($company['company']->id, $employee->id, '2026-09-02');
        $originalClock = $first->calculated_at?->toIso8601String();
        $later = AttendanceDayResultVersion::query()->create([
            'company_id' => $company['company']->id,
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
            'shift_version_id' => $first->shift_version_id,
            'status_code' => $first->status_code,
            'incomplete' => $first->incomplete,
            'late_seconds' => $first->late_seconds,
            'early_seconds' => $first->early_seconds,
            'selected_in_event_id' => $first->selected_in_event_id,
            'selected_out_event_id' => $first->selected_out_event_id,
            'generation_kind' => 'calculated',
            'previous_version_id' => $first->id,
            'calculated_at' => '2020-01-01 00:00:00',
        ]);
        $pointer = AttendanceDayResult::query()->where('employee_id', $employee->id)->first();
        $this->assertInstanceOf(AttendanceDayResult::class, $pointer);
        $pointer->current_version_id = $later->id;
        $pointer->save();

        $history = $this->getJson('/api/attendance/day-results/'.$employee->id.'/2026-09-02')->assertOk();

        $this->assertSame($first->id, $history->json('versions.0.id'));
        $this->assertSame($later->id, $history->json('versions.1.id'));
        $this->assertSame($first->id, $history->json('versions.1.previous_version_id'));
        $this->assertSame($later->id, $history->json('current_version_id'));
        $this->assertSame($originalClock, $first->fresh()->calculated_at?->toIso8601String());
        $this->assertSame('2026-09-02 09:00:00', $in->fresh()->event_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-02 17:00:00', $out->fresh()->event_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'));

        $this->punch($company['company'], $employee, '2026-09-02T18:00:00+06:00');
        $next = app(AttendanceDayProcessor::class)->calculate($company['company']->id, $employee->id, '2026-09-02');
        $this->assertSame($later->id, $next->previous_version_id);
        $this->assertSame($originalClock, $first->fresh()->calculated_at?->toIso8601String());
    }

    public function test_a_company_that_has_not_allowed_status_corrections_refuses_the_request(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Closed', [
            'present_status_code' => 'P',
            'correction_fields' => '',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-CLOSED', 'Synthetic Closed Policy');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $this->punch($company['company'], $employee, '2026-09-02T09:00:00+06:00');
        $this->punch($company['company'], $employee, '2026-09-02T17:00:00+06:00');
        $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
        ])->assertCreated();

        $this->postJson('/api/attendance/corrections', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic request',
        ])->assertStatus(422);
        $this->assertSame(0, AttendanceCorrection::query()->where('company_id', $company['company']->id)->count());
        $this->assertSame(1, AttendanceDayResultVersion::query()->where('employee_id', $employee->id)->count());
    }

    public function test_rejection_leaves_the_day_and_approval_adds_a_version_without_editing_punches(): void
    {
        $company = $this->preparedCompany('Harbor Attendance Decision', [
            'present_status_code' => 'P',
            'correction_fields' => 'status_code',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $rejectedEmployee = $this->employee($company['company'], 'SYN-WF-REJ', 'Synthetic Rejection');
        $approvedEmployee = $this->employee($company['company'], 'SYN-WF-APP', 'Synthetic Approval');
        $shift = $this->shift($company['company'], 'Day', '09:00:00', '17:00:00', false);
        foreach ([$rejectedEmployee, $approvedEmployee] as $employee) {
            $this->candidate($company['company'], $employee, $shift, 'default', null);
            $this->punch($company['company'], $employee, '2026-09-02T09:00:00+06:00');
            $this->punch($company['company'], $employee, '2026-09-02T17:00:00+06:00');
            $this->postJson('/api/attendance/day-results', [
                'employee_id' => $employee->id,
                'work_date' => '2026-09-02',
            ])->assertCreated();
        }
        $rejectedBefore = AttendanceDayResult::query()->where('employee_id', $rejectedEmployee->id)->value('current_version_id');
        $approvedPunch = PunchEvent::query()->whereHas('identity', function ($query) use ($approvedEmployee): void {
            $query->where('employee_id', $approvedEmployee->id);
        })->orderBy('event_at')->first();
        $approvedAt = $approvedPunch?->event_at?->toIso8601String();

        $rejected = $this->postJson('/api/attendance/corrections', [
            'employee_id' => $rejectedEmployee->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic rejection',
        ])->assertCreated();
        $this->postJson('/api/attendance/corrections/'.$rejected->json('id').'/decision', [
            'decision' => 'rejected',
        ])->assertOk()->assertJsonPath('correction_state', 'rejected');
        $approved = $this->postJson('/api/attendance/corrections', [
            'employee_id' => $approvedEmployee->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic approval',
        ])->assertCreated();
        $this->postJson('/api/attendance/corrections/'.$approved->json('id').'/decision', [
            'decision' => 'approved',
        ])->assertOk()->assertJsonPath('correction_state', 'applied');

        $this->assertSame($rejectedBefore, AttendanceDayResult::query()->where('employee_id', $rejectedEmployee->id)->value('current_version_id'));
        $this->assertSame(1, AttendanceDayResultVersion::query()->where('employee_id', $rejectedEmployee->id)->count());
        $this->assertSame(0, AttendanceCorrectionValue::query()->where('attendance_correction_id', $rejected->json('id'))->where('value_stage', 'approved')->count());
        $approvedDetail = $this->getJson('/api/attendance/day-results/'.$approvedEmployee->id.'/2026-09-02')->assertOk();
        $this->assertCount(2, $approvedDetail->json('versions'));
        $this->assertSame('SL', $approvedDetail->json('versions.1.status_code'));
        $this->assertSame('correction', $approvedDetail->json('versions.1.generation_kind'));
        $this->assertSame($approvedDetail->json('versions.0.selected_in_event_id'), $approvedDetail->json('versions.1.selected_in_event_id'));
        $approvedPunch?->refresh();
        $this->assertSame($approvedAt, $approvedPunch?->event_at?->toIso8601String());
    }

    public function test_next_day_two_does_not_pull_a_punch_from_the_next_calendar_date(): void
    {
        $this->assertFalse(LegacyShiftWindow::crossesMidnight(LegacyShiftWindow::SAME_DATE));
        $this->assertTrue(LegacyShiftWindow::crossesMidnight(LegacyShiftWindow::NEXT_DATE));
        $this->assertFalse(LegacyShiftWindow::crossesMidnight(LegacyShiftWindow::OUT_MARGIN_NEXT_DATE));
        $this->assertFalse(LegacyShiftWindow::punchMarginSupported());

        $company = $this->preparedCompany('Harbor Attendance Margin', [
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'incomplete_status_code' => 'A',
        ]);
        $employee = $this->employee($company['company'], 'SYN-WF-MARGIN', 'Synthetic Margin');
        $shift = $this->shift($company['company'], 'Out margin next day', '09:00:59', '17:30:00', false);
        $this->candidate($company['company'], $employee, $shift, 'default', null);
        $this->punch($company['company'], $employee, '2026-09-02T09:05:00+06:00');
        $nextDay = $this->punch($company['company'], $employee, '2026-09-03T01:00:00+06:00');

        $response = $this->postJson('/api/attendance/day-results', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
        ])->assertCreated();

        $this->assertTrue($response->json('versions.0.incomplete'));
        $this->assertNull($response->json('versions.0.selected_out_event_id'));
        $this->assertNotSame($nextDay->id, $response->json('versions.0.selected_in_event_id'));
        $this->assertNotNull(PunchEvent::query()->find($nextDay->id));
    }

    public function test_installed_vtl_fixtures_cover_the_representative_days_under_the_approved_window_policy(): void
    {
        $company = Company::query()->find('01M4KG2384JEKYAE5SXC927QDF');
        $this->assertInstanceOf(Company::class, $company);
        $this->assertSame(3, Employee::query()->where('company_id', $company->id)->where('source_system', 'legacy_vtlhrm')->count());
        $this->assertSame(15, Shift::query()->where('company_id', $company->id)->where('source_system', 'legacy_vtlhrm')->count());
        $policy = AttendancePolicy::query()->where('company_id', $company->id)->first();
        $this->assertInstanceOf(AttendancePolicy::class, $policy);
        $this->assertSame('roster', $policy->shift_winner);
        $this->assertSame('margin_first_and_last', $policy->punch_selection);
        $this->assertSame(['W', 'H', 'H', 'HP'], [$policy->working_day_code, $policy->non_working_day_code, $policy->non_working_status_code, $policy->non_working_attended_status_code]);
        $this->assertFalse($policy->missing_punch_requests);
        $this->assertSame(0, $policy->grace_seconds);
        $this->assertSame('', $policy->correction_fields);
        $corporate = ShiftVersion::query()->where('company_id', $company->id)->where('source_key', '14')->first();
        $night = ShiftVersion::query()->where('company_id', $company->id)->where('source_key', '12')->first();
        $this->assertInstanceOf(ShiftVersion::class, $corporate);
        $this->assertInstanceOf(ShiftVersion::class, $night);
        $this->assertFalse($corporate->crosses_midnight);
        $this->assertTrue($night->crosses_midnight);

        $anayet = $this->fixtureEmployee('463');
        $ragib = $this->fixtureEmployee('100450');
        $sabuj = $this->fixtureEmployee('100654');
        $synthetic = Employee::query()->where('company_id', $company->id)->where('source_system', 'synthetic_attendance_test')->where('source_key', 'night-1')->first();
        $this->assertInstanceOf(Employee::class, $synthetic);
        $normal = $this->fixtureVersion($anayet, '2026-09-02');
        $late = $this->fixtureVersion($ragib, '2026-09-03');
        $incomplete = $this->fixtureVersion($sabuj, '2026-09-13');
        $overnight = $this->fixtureVersion($synthetic, '2026-09-04');
        $this->assertFalse($normal->incomplete);
        $this->assertSame('P', $normal->status_code);
        $this->assertSame(0, $normal->late_seconds);
        $this->assertSame('attendance audit citation', PunchEvent::query()->find($normal->selected_in_event_id)?->source_name);
        $this->assertSame('synthetic_attendance_test', PunchEvent::query()->where('source_event_id', 'synthetic:463:2026-09-02T12:00:00')->value('source_name'));
        $this->assertNotSame($normal->selected_in_event_id, PunchEvent::query()->where('source_event_id', 'synthetic:463:2026-09-02T12:00:00')->value('id'));
        $this->assertSame(600, $late->late_seconds);
        $this->assertFalse($late->incomplete);
        $this->assertTrue($incomplete->incomplete);
        $this->assertSame('A', $incomplete->status_code);
        $this->assertNull($incomplete->selected_out_event_id);
        $this->assertFalse($overnight->incomplete);
        $this->assertSame(0, $overnight->late_seconds);
        $this->assertNotNull($overnight->selected_out_event_id);

        $user = $this->signInFor($company, false);
        $this->grant($user, $company);
        $this->postJson('/api/attendance/corrections', [
            'employee_id' => $anayet->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic request against the unchanged VTL policy',
        ])->assertStatus(422);
        $this->assertSame(0, AttendanceCorrection::query()->where('company_id', $company->id)->where('employee_id', $anayet->id)->count());
        $this->assertSame(3, Employee::query()->where('company_id', $company->id)->where('source_system', 'legacy_vtlhrm')->count());
        $this->assertSame(15, Shift::query()->where('company_id', $company->id)->where('source_system', 'legacy_vtlhrm')->count());
    }

    public function test_attendance_requires_an_explicit_company_when_the_operator_has_two_memberships(): void
    {
        $first = $this->company('Harbor Attendance First Company');
        $second = $this->company('Harbor Attendance Second Company');
        $user = $this->signInFor($first, false);
        CompanyMembership::query()->create([
            'user_id' => $user->id,
            'company_id' => $second->id,
            'status' => 'active',
            'can_manage_employees' => false,
            'can_manage_organization_masters' => false,
        ]);
        $this->app['session']->forget(CompanyContext::SESSION_KEY);

        $this->getJson('/api/attendance/day-results')
            ->assertForbidden()
            ->assertJsonPath('message', 'An active company must be selected.');
    }

    /**
     * @param  array<string, mixed>  $policy
     * @return array{company: Company, user: User}
     */
    private function preparedCompany(string $name, array $policy): array
    {
        $company = $this->company($name);
        $user = $this->signInFor($company, false);
        $this->grant($user, $company);
        AttendancePolicy::query()->create(array_merge([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'default',
            'punch_selection' => 'first_and_last',
            'grace_seconds' => null,
            'late_measured_from' => null,
            'early_threshold_seconds' => null,
            'present_status_code' => null,
            'incomplete_status_code' => null,
            'correction_fields' => '',
        ], $policy));

        return ['company' => $company, 'user' => $user];
    }

    private function grant(User $user, Company $company): void
    {
        PunchRecordingWriter::query()->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'authorized_at' => now(),
        ]);
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

    private function shift(Company $company, string $name, string $start, string $end, bool $crossesMidnight): Shift
    {
        $shift = Shift::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'source_system' => 'synthetic_attendance_test',
        ]);
        ShiftVersion::query()->create([
            'company_id' => $company->id,
            'shift_id' => $shift->id,
            'start_time' => $start,
            'end_time' => $end,
            'crosses_midnight' => $crossesMidnight,
            'effective_from' => '2026-01-01',
            'source_system' => 'synthetic_attendance_test',
        ]);

        return $shift;
    }

    private function candidate(Company $company, Employee $employee, Shift $shift, string $kind, ?string $workDate): void
    {
        ShiftAssignmentCandidate::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'candidate_kind' => $kind,
            'roster_work_date' => $workDate,
            'source_system' => 'synthetic_attendance_test',
        ]);
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

    private function fixtureEmployee(string $sourceKey): Employee
    {
        $employee = Employee::query()
            ->where('company_id', '01M4KG2384JEKYAE5SXC927QDF')
            ->where('source_system', 'legacy_vtlhrm')
            ->where('source_key', $sourceKey)
            ->first();
        $this->assertInstanceOf(Employee::class, $employee);

        return $employee;
    }

    private function fixtureVersion(Employee $employee, string $workDate): AttendanceDayResultVersion
    {
        $pointer = AttendanceDayResult::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->first();
        $version = $pointer instanceof AttendanceDayResult
            ? AttendanceDayResultVersion::query()->find($pointer->current_version_id)
            : null;
        $this->assertInstanceOf(AttendanceDayResultVersion::class, $version);

        return $version;
    }
}
