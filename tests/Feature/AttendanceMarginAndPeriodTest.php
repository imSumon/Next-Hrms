<?php

namespace Tests\Feature;

use App\Attendance\AttendanceDayProcessor;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePayrollActionGrant;
use App\Models\AttendancePayrollHandoff;
use App\Models\AttendancePayrollHandoffLine;
use App\Models\AttendancePeriodEvent;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchRecordingWriter;
use App\Models\RosterSourceDay;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\EmployeeDatabaseTestCase;

class AttendanceMarginAndPeriodTest extends EmployeeDatabaseTestCase
{
    public function test_roster_window_classifies_on_time_late_and_absent_like_the_legacy_rule(): void
    {
        $company = $this->windowCompany('Harbor Window Rule');
        $shift = $this->shift($company['company'], '09:00:59', '17:30:00');
        $cases = [
            'on-time' => [['2026-09-02T08:17:41+06:00', '2026-09-02T17:34:36+06:00'], 'P', 0],
            'late' => [['2026-09-02T09:10:59+06:00', '2026-09-02T17:40:00+06:00'], 'P', 600],
            'after-absent-limit' => [['2026-09-02T10:00:01+06:00', '2026-09-02T18:00:00+06:00'], 'A', null],
            'left-before-shift-end' => [['2026-09-02T08:30:00+06:00', '2026-09-02T17:00:00+06:00'], 'A', null],
            'next-morning-out-margin' => [['2026-09-02T08:30:00+06:00', '2026-09-03T05:50:00+06:00'], 'P', 0],
            'one-punch' => [['2026-09-02T08:30:00+06:00'], 'A', null],
            'no-punch' => [[], 'A', null],
        ];
        foreach ($cases as $code => [$punches, $status, $late]) {
            $employee = $this->employee($company['company'], 'SYN-WIN-'.$code);
            $this->rosterDay($company['company'], $employee, $shift, '2026-09-02', 'W');
            foreach ($punches as $punch) {
                $this->punch($company['company'], $employee, $punch);
            }
            $this->punch($company['company'], $employee, '2026-09-03T06:00:00+06:00');

            $version = app(AttendanceDayProcessor::class)->calculate($company['company']->id, $employee->id, '2026-09-02');

            $this->assertSame($status, $version->status_code, $code);
            $this->assertSame($late, $version->late_seconds, $code);
            $this->assertNotNull($version->roster_source_day_id, $code);
        }
    }

    public function test_non_working_roster_day_uses_policy_codes_and_an_unknown_day_code_is_refused(): void
    {
        $company = $this->windowCompany('Harbor Window Holiday');
        $shift = $this->shift($company['company'], '09:00:59', '17:30:00');
        $worked = $this->employee($company['company'], 'SYN-HOL-WORKED');
        $this->rosterDay($company['company'], $worked, $shift, '2026-09-04', 'H');
        $this->punch($company['company'], $worked, '2026-09-04T08:50:00+06:00');
        $this->punch($company['company'], $worked, '2026-09-04T17:45:00+06:00');
        $rested = $this->employee($company['company'], 'SYN-HOL-RESTED');
        $this->rosterDay($company['company'], $rested, $shift, '2026-09-04', 'H');
        $other = $this->employee($company['company'], 'SYN-HOL-OTHER');
        $this->rosterDay($company['company'], $other, $shift, '2026-09-04', 'O');
        $present = $this->employee($company['company'], 'SYN-HOL-ALWAYS');
        $this->rosterDay($company['company'], $present, $shift, '2026-09-04', 'W', true);

        $processor = app(AttendanceDayProcessor::class);
        $this->assertSame('HP', $processor->calculate($company['company']->id, $worked->id, '2026-09-04')->status_code);
        $this->assertSame('H', $processor->calculate($company['company']->id, $rested->id, '2026-09-04')->status_code);
        $this->assertSame('P', $processor->calculate($company['company']->id, $present->id, '2026-09-04')->status_code);
        $this->postJson('/api/attendance/day-results', ['employee_id' => $other->id, 'work_date' => '2026-09-04'])
            ->assertStatus(422)
            ->assertJsonPath('errors.source_day_code.0', 'The company policy has no attendance rule for this roster day code.');
        $this->assertSame(0, AttendanceDayResultVersion::query()->where('employee_id', $other->id)->count());
    }

    public function test_a_roster_day_without_a_window_fails_closed(): void
    {
        $company = $this->windowCompany('Harbor Window Missing');
        $shift = $this->shift($company['company'], '09:00:59', '17:30:00');
        $employee = $this->employee($company['company'], 'SYN-WIN-NONE');
        $this->candidate($company['company'], $employee, $shift, '2026-09-02');

        $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => '2026-09-02'])
            ->assertStatus(422)
            ->assertJsonPath('errors.work_date.0', 'This date has no roster day window.');
    }

    public function test_recalculation_refuses_to_replace_an_approved_correction(): void
    {
        $company = $this->windowCompany('Harbor Window Correction', ['correction_fields' => 'status_code']);
        $shift = $this->shift($company['company'], '09:00:59', '17:30:00');
        $employee = $this->employee($company['company'], 'SYN-WIN-CORR');
        $this->rosterDay($company['company'], $employee, $shift, '2026-09-02', 'W');
        $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => '2026-09-02'])->assertCreated();
        $correction = $this->postJson('/api/attendance/corrections', [
            'employee_id' => $employee->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic approved leave',
        ])->assertCreated();
        $this->postJson('/api/attendance/corrections/'.$correction->json('id').'/decision', ['decision' => 'approved'])->assertOk();

        $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => '2026-09-02'])
            ->assertStatus(422)
            ->assertJsonPath('errors.work_date.0', 'This day has an approved correction. Recalculation would replace it.');
        $this->assertSame(2, AttendanceDayResultVersion::query()->where('employee_id', $employee->id)->count());
    }

    public function test_period_finalization_reports_blockers_then_locks_and_reopens_with_a_reason(): void
    {
        $company = $this->windowCompany('Harbor Period Close', ['correction_fields' => 'status_code']);
        $shift = $this->shift($company['company'], '09:00:59', '17:30:00');
        $first = $this->employee($company['company'], 'SYN-PER-1');
        $second = $this->employee($company['company'], 'SYN-PER-2');
        foreach (['2026-09-01', '2026-09-02'] as $date) {
            $this->rosterDay($company['company'], $first, $shift, $date, 'W');
            $this->rosterDay($company['company'], $second, $shift, $date, 'W');
        }
        $this->punch($company['company'], $first, '2026-09-01T08:30:00+06:00');
        $this->punch($company['company'], $first, '2026-09-01T17:45:00+06:00');
        $this->punch($company['company'], $first, '2026-09-02T09:05:59+06:00');
        $this->punch($company['company'], $first, '2026-09-02T17:31:00+06:00');
        $this->punch($company['company'], $second, '2026-09-01T08:30:00+06:00');
        $period = $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-01', 'period_end' => '2026-09-02'])->assertCreated();
        $periodId = $period->json('period.id');
        $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-03'])->assertStatus(422);

        $blocked = $this->postJson('/api/attendance/periods/'.$periodId.'/finalize')->assertStatus(422);
        $this->assertContains('4 expected employee-days have no result.', $blocked->json('errors.period_state'));
        $this->assertContains('3 employee-days have punches but no result.', $blocked->json('errors.period_state'));

        foreach ([$first, $second] as $employee) {
            foreach (['2026-09-01', '2026-09-02'] as $date) {
                $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => $date])->assertCreated();
            }
        }
        $pending = $this->postJson('/api/attendance/corrections', [
            'employee_id' => $second->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic pending request',
        ])->assertCreated();
        $this->postJson('/api/attendance/periods/'.$periodId.'/finalize')
            ->assertStatus(422)
            ->assertJsonPath('errors.period_state.0', '1 corrections are still waiting for a decision.');
        $this->postJson('/api/attendance/corrections/'.$pending->json('id').'/decision', ['decision' => 'rejected'])->assertOk();

        $report = $this->getJson('/api/attendance/report?from=2026-09-01&to=2026-09-02')->assertOk();
        $this->assertTrue($report->json('reconciled'));
        $this->assertSame(4, $report->json('totals.processed'));
        $this->assertSame(2, $report->json('totals.present'));
        $this->assertSame(2, $report->json('totals.incomplete'));
        $this->assertSame(300, $report->json('totals.late_seconds'));
        $this->assertCount(2, $report->json('exceptions'));
        $this->assertSame([], $report->json('missing_results'));

        $finalized = $this->postJson('/api/attendance/periods/'.$periodId.'/finalize')->assertOk();
        $this->assertSame('finalized', $finalized->json('period.period_state'));
        $versionsBefore = AttendanceDayResultVersion::query()->where('company_id', $company['company']->id)->count();
        $this->postJson('/api/attendance/day-results', ['employee_id' => $first->id, 'work_date' => '2026-09-02'])
            ->assertStatus(422)
            ->assertJsonPath('errors.work_date.0', 'This attendance period is finalized. Reopen it before changing results.');
        $this->postJson('/api/attendance/corrections', [
            'employee_id' => $first->id,
            'work_date' => '2026-09-02',
            'status_code' => 'SL',
            'reason' => 'Synthetic late request',
        ])->assertStatus(422);
        $this->assertSame($versionsBefore, AttendanceDayResultVersion::query()->where('company_id', $company['company']->id)->count());

        $this->postJson('/api/attendance/periods/'.$periodId.'/reopen', ['reason' => ' '])->assertStatus(422);
        $reopened = $this->postJson('/api/attendance/periods/'.$periodId.'/reopen', ['reason' => 'Synthetic missing-punch review'])->assertOk();
        $this->assertSame('open', $reopened->json('period.period_state'));
        $this->assertSame(['opened', 'finalized', 'reopened'], array_column($reopened->json('events'), 'event_kind'));
        $this->assertSame('Synthetic missing-punch review', $reopened->json('events.2.reason'));
        $this->postJson('/api/attendance/day-results', ['employee_id' => $first->id, 'work_date' => '2026-09-02'])->assertOk();
        $event = AttendancePeriodEvent::query()->where('attendance_period_id', $periodId)->first();
        $this->assertInstanceOf(AttendancePeriodEvent::class, $event);
        $this->expectException(QueryException::class);
        DB::table('attendance_period_events')->where('id', $event->id)->update(['reason' => 'rewritten']);
    }

    public function test_payroll_handoff_needs_a_finalized_period_reconciles_and_is_not_repeated(): void
    {
        $company = $this->windowCompany('Harbor Payroll Handoff');
        $shift = $this->shift($company['company'], '09:00:59', '17:30:00');
        $employee = $this->employee($company['company'], 'SYN-PAY-1');
        $this->rosterDay($company['company'], $employee, $shift, '2026-09-03', 'W');
        $this->rosterDay($company['company'], $employee, $shift, '2026-09-04', 'H');
        $this->punch($company['company'], $employee, '2026-09-03T09:10:59+06:00');
        $this->punch($company['company'], $employee, '2026-09-03T17:40:00+06:00');
        foreach (['2026-09-03', '2026-09-04'] as $date) {
            $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => $date])->assertCreated();
        }
        $periodId = $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-03', 'period_end' => '2026-09-04'])->json('period.id');
        $this->postJson('/api/attendance/periods/'.$periodId.'/payroll-handoff')
            ->assertStatus(422)
            ->assertJsonPath('errors.period_state.0', 'Payroll handoff requires a finalized attendance period.');
        $this->postJson('/api/attendance/periods/'.$periodId.'/finalize')->assertOk();

        $first = $this->postJson('/api/attendance/periods/'.$periodId.'/payroll-handoff')->assertCreated();
        $repeat = $this->postJson('/api/attendance/periods/'.$periodId.'/payroll-handoff')->assertOk();

        $this->assertSame($first->json('id'), $repeat->json('id'));
        $this->assertFalse($repeat->json('created'));
        $this->assertSame(1, $first->json('employee_count'));
        $this->assertSame(2, $first->json('line_count'));
        $this->assertSame(1, $first->json('present_count'));
        $this->assertSame(1, $first->json('non_working_count'));
        $this->assertSame(600, $first->json('late_seconds_total'));
        $this->assertSame(['present', 'non_working'], array_column($first->json('lines'), 'day_class'));
        $this->assertSame(1, AttendancePayrollHandoff::query()->where('attendance_period_id', $periodId)->count());

        $this->postJson('/api/attendance/periods/'.$periodId.'/reopen', ['reason' => 'Synthetic payroll query'])->assertOk();
        $this->assertSame('superseded', AttendancePayrollHandoff::query()->find($first->json('id'))?->handoff_state);
        $this->postJson('/api/attendance/periods/'.$periodId.'/finalize')->assertOk();
        $second = $this->postJson('/api/attendance/periods/'.$periodId.'/payroll-handoff')->assertCreated();
        $this->assertNotSame($first->json('id'), $second->json('id'));
        $this->assertSame(2, AttendancePayrollHandoffLine::query()->where('attendance_payroll_handoff_id', $first->json('id'))->count());
    }

    public function test_periods_stay_inside_the_company_and_need_the_writer_grant(): void
    {
        $own = $this->windowCompany('Harbor Period Own');
        $periodId = $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-01', 'period_end' => '2026-09-30'])->json('period.id');
        $other = $this->windowCompany('Harbor Period Other');

        $this->getJson('/api/attendance/periods/'.$periodId)->assertNotFound();
        $this->postJson('/api/attendance/periods/'.$periodId.'/finalize')->assertNotFound();
        $this->assertSame([], $this->getJson('/api/attendance/periods')->json('data'));

        $ungranted = $this->company('Harbor Period Ungranted');
        $this->signInFor($ungranted, false);
        $this->getJson('/api/attendance/periods')->assertForbidden();
        $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-01', 'period_end' => '2026-09-30'])->assertForbidden();
        $this->getJson('/api/attendance/report?from=2026-09-01&to=2026-09-30')->assertForbidden();
        $this->assertNotSame($own['company']->id, $other['company']->id);
    }

    /**
     * @param  array<string, mixed>  $policy
     * @return array{company: Company, user: User}
     */
    private function windowCompany(string $name, array $policy = []): array
    {
        $company = $this->company($name);
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'authorized_at' => now(),
        ]);
        foreach ([AttendancePayrollActionGrant::FINALIZE_PERIOD, AttendancePayrollActionGrant::REOPEN_PERIOD, AttendancePayrollActionGrant::HANDOFF_PERIOD] as $action) {
            AttendancePayrollActionGrant::query()->insert([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'action_key' => $action,
                'authorized_at' => now(),
            ]);
        }
        AttendancePolicy::query()->create(array_merge([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'roster',
            'punch_selection' => 'margin_first_and_last',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'early_threshold_seconds' => null,
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'correction_fields' => '',
            'working_day_code' => 'W',
            'non_working_day_code' => 'H',
            'non_working_status_code' => 'H',
            'non_working_attended_status_code' => 'HP',
        ], $policy));

        return ['company' => $company, 'user' => $user];
    }

    private function company(string $name): Company
    {
        return Company::query()->create([
            'company_name' => $name,
            'status' => 'Active',
        ]);
    }

    private function employee(Company $company, string $code): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'employee_code' => $code,
            'display_name' => 'Synthetic '.$code,
        ]);
    }

    private function shift(Company $company, string $start, string $end): Shift
    {
        $shift = Shift::query()->create([
            'company_id' => $company->id,
            'name' => 'Synthetic window day',
            'source_system' => 'synthetic_attendance_test',
        ]);
        ShiftVersion::query()->create([
            'company_id' => $company->id,
            'shift_id' => $shift->id,
            'start_time' => $start,
            'end_time' => $end,
            'crosses_midnight' => false,
            'effective_from' => '2026-01-01',
            'source_system' => 'synthetic_attendance_test',
        ]);

        return $shift;
    }

    private function candidate(Company $company, Employee $employee, Shift $shift, string $workDate): void
    {
        ShiftAssignmentCandidate::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'candidate_kind' => 'roster',
            'roster_work_date' => $workDate,
            'source_system' => 'synthetic_attendance_test',
        ]);
    }

    /**
     * A synthetic roster day shaped like a legacy next_day=2 calendar row:
     * in margin 07:30, absent after 10:00, out margin 05:55 on the next date.
     */
    private function rosterDay(Company $company, Employee $employee, Shift $shift, string $workDate, string $dayCode, bool $alwaysPresent = false): void
    {
        $this->candidate($company, $employee, $shift, $workDate);
        $nextDate = date('Y-m-d', strtotime($workDate.' +1 day'));
        RosterSourceDay::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'work_date' => $workDate,
            'source_day_code' => $dayCode,
            'always_present' => $alwaysPresent,
            'source_roster_flag' => 'No',
            'window_starts_at' => Carbon::parse($workDate.'T07:30:00+06:00')->utc(),
            'window_ends_at' => Carbon::parse($nextDate.'T05:55:00+06:00')->utc(),
            'shift_starts_at' => Carbon::parse($workDate.'T09:00:59+06:00')->utc(),
            'shift_ends_at' => Carbon::parse($workDate.'T17:30:00+06:00')->utc(),
            'absent_after_at' => Carbon::parse($workDate.'T10:00:00+06:00')->utc(),
            'source_system' => 'synthetic_attendance_test',
            'source_key' => $employee->employee_code.':'.$workDate,
            'source_catalog_key' => 'synthetic',
            'source_employee_key' => $employee->employee_code,
            'source_shift_key' => 'synthetic',
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
}
