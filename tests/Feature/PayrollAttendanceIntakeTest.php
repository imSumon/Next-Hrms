<?php

namespace Tests\Feature;

use App\Models\AttendancePayrollActionGrant;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollRunAttendanceInput;
use App\Models\PunchRecordingWriter;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\EmployeeDatabaseTestCase;

class PayrollAttendanceIntakeTest extends EmployeeDatabaseTestCase
{
    private const KEYS = ['attendance.day_count', 'attendance.present_days', 'attendance.absent_or_incomplete_days', 'attendance.late_seconds'];

    public function test_a_run_takes_a_finalized_handoff_once_and_the_copied_counts_reconcile(): void
    {
        $setup = $this->handedOffPeriod('Harbor Payroll Intake');
        $payrollPeriod = $this->postJson('/api/payroll/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-03', 'label' => 'Synthetic September slice'])->assertCreated();
        $run = $this->postJson('/api/payroll/periods/'.$payrollPeriod->json('id').'/runs', ['input_keys' => self::KEYS])->assertCreated();
        $this->assertSame('uses_declared_attendance', $run->json('attendance_usage'));
        $this->assertSame(1, $run->json('run_sequence'));

        $taken = $this->postJson('/api/payroll/runs/'.$run->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])->assertCreated();
        $repeat = $this->postJson('/api/payroll/runs/'.$run->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])->assertOk();

        $this->assertFalse($repeat->json('created'));
        $values = collect($taken->json('attendance_inputs'))->groupBy('employee_id')->map(fn ($rows) => collect($rows)->pluck('copied_value', 'input_key')->all());
        $this->assertSame(['attendance.absent_or_incomplete_days' => 0, 'attendance.day_count' => 2, 'attendance.late_seconds' => 600, 'attendance.present_days' => 2], $values[$setup['present']->id]);
        $this->assertSame(['attendance.absent_or_incomplete_days' => 2, 'attendance.day_count' => 2, 'attendance.late_seconds' => 0, 'attendance.present_days' => 0], $values[$setup['absent']->id]);
        $this->assertSame(8, PayrollRunAttendanceInput::query()->where('payroll_run_id', $run->json('id'))->count());

        $this->expectException(QueryException::class);
        DB::table('payroll_run_attendance_inputs')->where('payroll_run_id', $run->json('id'))->update(['copied_value' => 99]);
    }

    public function test_mismatched_dates_and_runs_without_attendance_inputs_are_refused(): void
    {
        $setup = $this->handedOffPeriod('Harbor Payroll Refuse');
        $wrongPeriod = $this->postJson('/api/payroll/periods', ['period_start' => '2026-09-01', 'period_end' => '2026-09-30', 'label' => 'Synthetic month'])->assertCreated();
        $run = $this->postJson('/api/payroll/periods/'.$wrongPeriod->json('id').'/runs', ['input_keys' => self::KEYS])->assertCreated();
        $this->postJson('/api/payroll/runs/'.$run->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance_payroll_handoff_id.0', 'The attendance period and the payroll period do not cover the same dates.');
        $this->postJson('/api/payroll/periods/'.$wrongPeriod->json('id').'/runs', ['input_keys' => []])
            ->assertStatus(422)
            ->assertJsonPath('errors.payroll_period_id.0', 'This payroll period already has a run in progress.');
        $this->postJson('/api/payroll/periods/'.$wrongPeriod->json('id').'/runs', ['input_keys' => ['attendance.overtime_pay']])
            ->assertStatus(422)
            ->assertJsonPath('errors.input_keys.0', 'Unknown payroll input: attendance.overtime_pay.');

        $samePeriod = $this->postJson('/api/payroll/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-03', 'label' => 'Synthetic slice'])->assertCreated();
        $noAttendance = $this->postJson('/api/payroll/periods/'.$samePeriod->json('id').'/runs', ['input_keys' => []])->assertCreated();
        $this->assertSame('declares_no_attendance', $noAttendance->json('attendance_usage'));
        $this->postJson('/api/payroll/runs/'.$noAttendance->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])
            ->assertStatus(422)
            ->assertJsonPath('errors.payroll_run_id.0', 'Only an in-progress run that declares attendance inputs can take a handoff.');
        $this->assertSame(0, PayrollRunAttendanceInput::query()->where('company_id', $setup['company']->id)->count());
    }

    public function test_reopening_attendance_supersedes_the_run_and_a_new_run_takes_the_new_handoff(): void
    {
        $setup = $this->handedOffPeriod('Harbor Payroll Reopen');
        $payrollPeriod = $this->postJson('/api/payroll/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-03', 'label' => 'Synthetic slice'])->assertCreated();
        $first = $this->postJson('/api/payroll/periods/'.$payrollPeriod->json('id').'/runs', ['input_keys' => self::KEYS])->assertCreated();
        $this->postJson('/api/payroll/runs/'.$first->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])->assertCreated();

        $this->postJson('/api/attendance/periods/'.$setup['period'].'/reopen', ['reason' => 'Synthetic payroll query'])->assertOk();
        $this->assertSame('superseded', PayrollRun::query()->find($first->json('id'))?->run_state);
        $this->postJson('/api/payroll/runs/'.$first->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])->assertOk();

        $this->postJson('/api/attendance/periods/'.$setup['period'].'/finalize')->assertOk();
        $newHandoff = $this->postJson('/api/attendance/periods/'.$setup['period'].'/payroll-handoff')->assertCreated();
        $this->postJson('/api/payroll/runs/'.$first->json('id').'/attendance', ['attendance_payroll_handoff_id' => $newHandoff->json('id')])
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance_payroll_handoff_id.0', 'This run already took a different attendance handoff.');
        $second = $this->postJson('/api/payroll/periods/'.$payrollPeriod->json('id').'/runs', ['input_keys' => self::KEYS])->assertCreated();
        $this->assertSame(2, $second->json('run_sequence'));
        $this->assertSame($first->json('id'), $second->json('previous_run_id'));
        $this->postJson('/api/payroll/runs/'.$second->json('id').'/attendance', ['attendance_payroll_handoff_id' => $setup['handoff']])
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance_payroll_handoff_id.0', 'Only an exported attendance handoff in this company can be taken.');
        $this->postJson('/api/payroll/runs/'.$second->json('id').'/attendance', ['attendance_payroll_handoff_id' => $newHandoff->json('id')])->assertCreated();
    }

    public function test_payroll_intake_needs_its_grant_and_cannot_take_another_companys_handoff(): void
    {
        $other = $this->handedOffPeriod('Harbor Payroll Other');
        $own = $this->handedOffPeriod('Harbor Payroll Own');
        $payrollPeriod = $this->postJson('/api/payroll/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-03', 'label' => 'Synthetic slice'])->assertCreated();
        $run = $this->postJson('/api/payroll/periods/'.$payrollPeriod->json('id').'/runs', ['input_keys' => self::KEYS])->assertCreated();

        $this->postJson('/api/payroll/runs/'.$run->json('id').'/attendance', ['attendance_payroll_handoff_id' => $other['handoff']])
            ->assertStatus(422)
            ->assertJsonPath('errors.attendance_payroll_handoff_id.0', 'Only an exported attendance handoff in this company can be taken.');

        AttendancePayrollActionGrant::query()->where('user_id', $own['user']->id)->where('action_key', AttendancePayrollActionGrant::CONSUME_HANDOFF)->delete();
        $this->getJson('/api/payroll/periods')->assertForbidden()->assertJsonPath('action', AttendancePayrollActionGrant::CONSUME_HANDOFF);
        $this->postJson('/api/payroll/runs/'.$run->json('id').'/attendance', ['attendance_payroll_handoff_id' => $own['handoff']])->assertForbidden();
        $this->assertSame(0, PayrollRunAttendanceInput::query()->whereIn('company_id', [$own['company']->id, $other['company']->id])->count());
    }

    /**
     * Two synthetic employees over two days: one present (one day 600 seconds late),
     * one with no punches.
     *
     * @return array{company: Company, user: User, period: string, handoff: string, present: Employee, absent: Employee}
     */
    private function handedOffPeriod(string $name): array
    {
        $company = Company::query()->create(['company_name' => $name, 'status' => 'Active']);
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert(['user_id' => $user->id, 'company_id' => $company->id, 'authorized_at' => now()]);
        foreach (AttendancePayrollActionGrant::ACTIONS as $action) {
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
        $employees = [];
        foreach (['SYN-PAY-PRESENT', 'SYN-PAY-ABSENT'] as $code) {
            $employees[$code] = Employee::query()->create(['company_id' => $company->id, 'employee_code' => $code, 'display_name' => 'Synthetic '.$code]);
            ShiftAssignmentCandidate::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employees[$code]->id,
                'shift_id' => $shift->id,
                'candidate_kind' => 'default',
                'source_system' => 'synthetic_attendance_test',
            ]);
        }
        foreach (['2026-09-02T09:10:00+06:00', '2026-09-02T17:00:00+06:00', '2026-09-03T08:55:00+06:00', '2026-09-03T17:05:00+06:00'] as $eventAt) {
            app(PunchRecordingRegistry::class)->record($company->id, [
                'employee_id' => $employees['SYN-PAY-PRESENT']->id,
                'identity_value' => 'SYN-PAY-PRESENT',
                'source_name' => 'synthetic attendance test',
                'event_at' => $eventAt,
            ]);
        }
        foreach ($employees as $employee) {
            foreach (['2026-09-02', '2026-09-03'] as $date) {
                $this->postJson('/api/attendance/day-results', ['employee_id' => $employee->id, 'work_date' => $date])->assertCreated();
            }
        }
        $period = $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-03'])->assertCreated()->json('period.id');
        $this->postJson('/api/attendance/periods/'.$period.'/finalize')->assertOk();
        $handoff = $this->postJson('/api/attendance/periods/'.$period.'/payroll-handoff')->assertCreated()->json('id');

        return ['company' => $company, 'user' => $user, 'period' => $period, 'handoff' => $handoff, 'present' => $employees['SYN-PAY-PRESENT'], 'absent' => $employees['SYN-PAY-ABSENT']];
    }
}
