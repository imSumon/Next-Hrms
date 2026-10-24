<?php

namespace Tests\Feature;

use App\Attendance\AttendanceCorrectionRegistry;
use App\Attendance\AttendanceDayProcessor;
use App\Models\AttendanceCorrectionValue;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Punch\PunchRecordingRegistry;
use Illuminate\Validation\ValidationException;
use Tests\EmployeeDatabaseTestCase;

class AttendanceDayProcessingTest extends EmployeeDatabaseTestCase
{
    public function test_first_and_last_punches_are_selected_and_the_middle_event_remains(): void
    {
        $company = $this->company('Harbor Attendance North');
        $employee = $this->employee($company, 'SYN-ATT-1', 'Synthetic North One');
        $this->signInFor($company);
        $this->policy($company, [
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $shift = $this->shift($company, 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);
        $first = $this->punch($company, $employee, '2026-09-02T08:17:41+06:00');
        $middle = $this->punch($company, $employee, '2026-09-02T12:00:00+06:00');
        $last = $this->punch($company, $employee, '2026-09-02T17:34:36+06:00');

        $version = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');

        $this->assertSame($first->id, $version->selected_in_event_id);
        $this->assertSame($last->id, $version->selected_out_event_id);
        $this->assertNotSame($middle->id, $version->selected_in_event_id);
        $this->assertNotSame($middle->id, $version->selected_out_event_id);
        $this->assertFalse($version->incomplete);
        $this->assertSame('P', $version->status_code);
        $this->assertSame(3, PunchEvent::query()->where('company_id', $company->id)->count());
        $this->assertNotNull(PunchEvent::query()->find($middle->id));
    }

    public function test_incomplete_status_and_grace_come_from_the_company_policy(): void
    {
        $letters = $this->company('Harbor Attendance Letters');
        $words = $this->company('Harbor Attendance Words');
        $letterEmployee = $this->employee($letters, 'SYN-ATT-2', 'Synthetic Letter Two');
        $wordEmployee = $this->employee($words, 'SYN-ATT-3', 'Synthetic Word Three');
        $this->signInFor($letters);
        $this->policy($letters, [
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
        ]);
        $this->policy($words, [
            'present_status_code' => 'Present',
            'incomplete_status_code' => 'Present',
            'grace_seconds' => 120,
            'late_measured_from' => 'shift_start',
        ]);
        $letterShift = $this->shift($letters, 'Letter day', '09:00:00', '17:00:00', false);
        $wordShift = $this->shift($words, 'Word day', '09:00:00', '17:00:00', false);
        $this->candidate($letters, $letterEmployee, $letterShift, 'default', null);
        $this->candidate($words, $wordEmployee, $wordShift, 'default', null);
        $this->punch($letters, $letterEmployee, '2026-09-02T09:00:30+06:00');
        $this->punch($words, $wordEmployee, '2026-09-02T09:00:30+06:00');

        $letterDay = app(AttendanceDayProcessor::class)->calculate($letters->id, $letterEmployee->id, '2026-09-02');
        $wordDay = app(AttendanceDayProcessor::class)->calculate($words->id, $wordEmployee->id, '2026-09-02');

        $this->assertTrue($letterDay->incomplete);
        $this->assertSame('A', $letterDay->status_code);
        $this->assertSame(30, $letterDay->late_seconds);
        $this->assertNull($letterDay->selected_out_event_id);
        $this->assertTrue($wordDay->incomplete);
        $this->assertSame('Present', $wordDay->status_code);
        $this->assertSame(0, $wordDay->late_seconds);
    }

    public function test_late_duration_can_start_at_the_end_of_grace(): void
    {
        $company = $this->company('Harbor Attendance Grace');
        $employee = $this->employee($company, 'SYN-ATT-4', 'Synthetic Grace Four');
        $this->signInFor($company);
        $this->policy($company, [
            'grace_seconds' => 60,
            'late_measured_from' => 'grace_end',
        ]);
        $shift = $this->shift($company, 'Grace day', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);
        $this->punch($company, $employee, '2026-09-02T09:02:00+06:00');
        $this->punch($company, $employee, '2026-09-02T17:00:00+06:00');

        $version = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');

        $this->assertSame(60, $version->late_seconds);
        $this->assertFalse($version->incomplete);
        $this->assertNull($version->status_code);
    }

    public function test_roster_candidate_wins_only_when_the_policy_says_roster(): void
    {
        $company = $this->company('Harbor Attendance Roster');
        $employee = $this->employee($company, 'SYN-ATT-5', 'Synthetic Roster Five');
        $this->signInFor($company);
        $this->policy($company, [
            'shift_winner' => 'roster',
            'early_threshold_seconds' => 0,
        ]);
        $defaultShift = $this->shift($company, 'Default end', '09:00:00', '17:00:00', false);
        $rosterShift = $this->shift($company, 'Roster end', '09:00:00', '18:00:00', false);
        $this->candidate($company, $employee, $defaultShift, 'default', null);
        $this->candidate($company, $employee, $rosterShift, 'roster', '2026-09-02');
        $this->punch($company, $employee, '2026-09-02T09:00:00+06:00');
        $this->punch($company, $employee, '2026-09-02T17:30:00+06:00');

        $version = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');

        $rosterVersion = ShiftVersion::query()->where('shift_id', $rosterShift->id)->first();
        $this->assertSame($rosterVersion->id, $version->shift_version_id);
        $this->assertSame(1800, $version->early_seconds);
    }

    public function test_a_missing_roster_candidate_does_not_fall_back_to_the_default_shift(): void
    {
        $company = $this->company('Harbor Attendance Missing Roster');
        $employee = $this->employee($company, 'SYN-ATT-6', 'Synthetic Missing Six');
        $this->signInFor($company);
        $this->policy($company, ['shift_winner' => 'roster']);
        $shift = $this->shift($company, 'Default only', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);

        $this->expectException(ValidationException::class);
        try {
            app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');
        } finally {
            $this->assertSame(0, AttendanceDayResult::query()->where('company_id', $company->id)->count());
        }
    }

    public function test_a_cross_midnight_shift_keeps_events_inside_its_span(): void
    {
        $company = $this->company('Harbor Attendance Night');
        $employee = $this->employee($company, 'SYN-ATT-7', 'Synthetic Night Seven');
        $this->signInFor($company);
        $this->policy($company, ['present_status_code' => 'P']);
        $shift = $this->shift($company, 'Night', '22:00:00', '06:00:00', true);
        $this->candidate($company, $employee, $shift, 'default', null);
        $in = $this->punch($company, $employee, '2026-09-02T22:15:00+06:00');
        $out = $this->punch($company, $employee, '2026-09-03T05:40:00+06:00');
        $this->punch($company, $employee, '2026-09-03T12:00:00+06:00');

        $version = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');

        $this->assertSame($in->id, $version->selected_in_event_id);
        $this->assertSame($out->id, $version->selected_out_event_id);
        $this->assertFalse($version->incomplete);
        $this->assertSame('P', $version->status_code);
    }

    public function test_recalculation_keeps_the_previous_version(): void
    {
        $company = $this->company('Harbor Attendance Recalc');
        $employee = $this->employee($company, 'SYN-ATT-8', 'Synthetic Recalc Eight');
        $this->signInFor($company);
        $this->policy($company, ['present_status_code' => 'P']);
        $shift = $this->shift($company, 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);
        $this->punch($company, $employee, '2026-09-02T09:00:00+06:00');
        $firstOut = $this->punch($company, $employee, '2026-09-02T16:00:00+06:00');
        $first = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');
        $secondOut = $this->punch($company, $employee, '2026-09-02T18:00:00+06:00');

        $second = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');

        $this->assertSame($firstOut->id, $first->fresh()->selected_out_event_id);
        $this->assertSame($secondOut->id, $second->selected_out_event_id);
        $this->assertSame($first->id, $second->previous_version_id);
        $this->assertSame($second->id, AttendanceDayResult::query()->where('employee_id', $employee->id)->first()->current_version_id);
        $this->assertSame(2, AttendanceDayResultVersion::query()->where('employee_id', $employee->id)->count());
    }

    public function test_an_approved_correction_adds_a_version_and_leaves_punch_events_unchanged(): void
    {
        $company = $this->company('Harbor Attendance Correction');
        $employee = $this->employee($company, 'SYN-ATT-9', 'Synthetic Correction Nine');
        $this->signInFor($company);
        $this->policy($company, [
            'present_status_code' => 'P',
            'correction_fields' => 'status_code',
        ]);
        $shift = $this->shift($company, 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);
        $in = $this->punch($company, $employee, '2026-09-02T09:00:00+06:00');
        $out = $this->punch($company, $employee, '2026-09-02T17:00:00+06:00');
        $calculated = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');
        $correction = app(AttendanceCorrectionRegistry::class)->submit(
            $company->id,
            $employee->id,
            '2026-09-02',
            'SL',
            'Synthetic status correction',
        );

        $applied = app(AttendanceCorrectionRegistry::class)->decide($company->id, $correction->id, 'approved');

        $this->assertSame('applied', $applied->correction_state);
        $current = AttendanceDayResultVersion::query()->find($applied->applied_version_id);
        $this->assertSame('SL', $current->status_code);
        $this->assertSame('correction', $current->generation_kind);
        $this->assertSame($calculated->id, $current->previous_version_id);
        $this->assertSame('P', $calculated->fresh()->status_code);
        $this->assertSame('2026-09-02 09:00:00', $in->fresh()->event_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-02 17:00:00', $out->fresh()->event_at->timezone('Asia/Dhaka')->format('Y-m-d H:i:s'));
        $stages = AttendanceCorrectionValue::query()->where('attendance_correction_id', $correction->id)->pluck('field_value', 'value_stage');
        $this->assertSame('P', $stages['original']);
        $this->assertSame('SL', $stages['requested']);
        $this->assertSame('SL', $stages['approved']);
        $this->assertSame('SL', $stages['final']);
    }

    public function test_a_rejected_correction_does_not_replace_the_day(): void
    {
        $company = $this->company('Harbor Attendance Rejection');
        $employee = $this->employee($company, 'SYN-ATT-10', 'Synthetic Rejection Ten');
        $this->signInFor($company);
        $this->policy($company, [
            'present_status_code' => 'P',
            'correction_fields' => 'status_code',
        ]);
        $shift = $this->shift($company, 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);
        $this->punch($company, $employee, '2026-09-02T09:00:00+06:00');
        $this->punch($company, $employee, '2026-09-02T17:00:00+06:00');
        $calculated = app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');
        $correction = app(AttendanceCorrectionRegistry::class)->submit(
            $company->id,
            $employee->id,
            '2026-09-02',
            'A',
            'Synthetic rejection',
        );

        $rejected = app(AttendanceCorrectionRegistry::class)->decide($company->id, $correction->id, 'rejected');

        $this->assertSame('rejected', $rejected->correction_state);
        $this->assertNull($rejected->applied_version_id);
        $this->assertSame($calculated->id, AttendanceDayResult::query()->where('employee_id', $employee->id)->first()->current_version_id);
        $this->assertSame(0, AttendanceCorrectionValue::query()->where('attendance_correction_id', $correction->id)->where('value_stage', 'approved')->count());
    }

    public function test_status_correction_is_refused_when_the_company_has_not_allowed_it(): void
    {
        $company = $this->company('Harbor Attendance Closed Correction');
        $employee = $this->employee($company, 'SYN-ATT-11', 'Synthetic Closed Eleven');
        $this->signInFor($company);
        $this->policy($company, ['present_status_code' => 'P']);
        $shift = $this->shift($company, 'Day', '09:00:00', '17:00:00', false);
        $this->candidate($company, $employee, $shift, 'default', null);
        $this->punch($company, $employee, '2026-09-02T09:00:00+06:00');
        $this->punch($company, $employee, '2026-09-02T17:00:00+06:00');
        app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');

        $this->expectException(ValidationException::class);
        app(AttendanceCorrectionRegistry::class)->submit($company->id, $employee->id, '2026-09-02', 'A', 'Not allowed');
    }

    public function test_calculation_without_a_policy_stores_nothing(): void
    {
        $company = $this->company('Harbor Attendance No Policy');
        $employee = $this->employee($company, 'SYN-ATT-12', 'Synthetic Policy Twelve');
        $this->signInFor($company);

        $this->expectException(ValidationException::class);
        try {
            app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, '2026-09-02');
        } finally {
            $this->assertSame(0, AttendanceDayResult::query()->where('company_id', $company->id)->count());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function policy(Company $company, array $overrides = []): AttendancePolicy
    {
        return AttendancePolicy::query()->create(array_merge([
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
        ], $overrides));
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
