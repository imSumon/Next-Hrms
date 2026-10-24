<?php

namespace App\Payroll;

use App\Attendance\AttendancePeriodReport;
use App\Audit\EmployeeAuditTrail;
use App\Models\AttendancePayrollHandoff;
use App\Models\AttendancePayrollHandoffLine;
use App\Models\AttendancePeriod;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunAttendanceInput;
use App\Models\PayrollRunDeclaredInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Payroll's consumer of a finalized attendance handoff. It copies declared
 * attendance counts per employee; it computes no money.
 */
class PayrollAttendanceIntake
{
    /**
     * @var array<string, string>
     */
    public const ATTENDANCE_INPUTS = [
        'attendance.day_count' => 'Days handed over',
        'attendance.present_days' => 'Present days',
        'attendance.absent_or_incomplete_days' => 'Absent or incomplete days',
        'attendance.non_working_days' => 'Non-working days',
        'attendance.other_status_days' => 'Days with another status',
        'attendance.late_seconds' => 'Late seconds',
    ];

    public function openPeriod(string $companyId, string $periodStart, string $periodEnd, string $label): PayrollPeriod
    {
        $this->actorId();
        app(AttendancePeriodReport::class)->assertRange($periodStart, $periodEnd);
        $label = trim($label);
        if ($label === '') {
            throw ValidationException::withMessages(['label' => 'A payroll period needs a label.']);
        }
        $exists = PayrollPeriod::query()->where('company_id', $companyId)->whereDate('period_start', $periodStart)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['period_start' => 'A payroll period already starts on this date.']);
        }

        return PayrollPeriod::query()->create([
            'company_id' => $companyId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'label' => $label,
        ]);
    }

    /**
     * @param  list<string>  $inputKeys
     */
    public function startRun(string $companyId, string $payrollPeriodId, array $inputKeys): PayrollRun
    {
        $actorId = $this->actorId();
        $inputKeys = array_values(array_unique($inputKeys));
        foreach ($inputKeys as $key) {
            if (! array_key_exists($key, self::ATTENDANCE_INPUTS)) {
                throw ValidationException::withMessages(['input_keys' => 'Unknown payroll input: '.$key.'.']);
            }
        }

        return DB::transaction(function () use ($companyId, $payrollPeriodId, $inputKeys, $actorId): PayrollRun {
            $period = PayrollPeriod::query()->where('company_id', $companyId)->where('id', $payrollPeriodId)->lockForUpdate()->first();
            if (! $period instanceof PayrollPeriod) {
                throw ValidationException::withMessages(['payroll_period_id' => 'The payroll period is not in this company.']);
            }
            $runs = PayrollRun::query()->where('company_id', $companyId)->where('payroll_period_id', $period->id)->orderBy('run_sequence')->get();
            if ($runs->contains(fn (PayrollRun $run): bool => $run->run_state === 'in_progress')) {
                throw ValidationException::withMessages(['payroll_period_id' => 'This payroll period already has a run in progress.']);
            }
            $run = PayrollRun::query()->create([
                'company_id' => $companyId,
                'payroll_period_id' => $period->id,
                'run_sequence' => ($runs->max('run_sequence') ?? 0) + 1,
                'run_state' => 'in_progress',
                'attendance_usage' => $inputKeys === [] ? 'declares_no_attendance' : 'uses_declared_attendance',
                'created_by_user_id' => $actorId,
                'previous_run_id' => $runs->last()?->id,
            ]);
            foreach ($inputKeys as $key) {
                PayrollRunDeclaredInput::query()->create([
                    'company_id' => $companyId,
                    'payroll_run_id' => $run->id,
                    'input_key' => $key,
                ]);
            }

            return $run;
        });
    }

    /**
     * Copy the declared attendance counts from one exported handoff into the run.
     * A repeat with the same handoff returns the run unchanged.
     *
     * @return array{run: PayrollRun, created: bool}
     */
    public function takeAttendance(string $companyId, string $payrollRunId, string $handoffId): array
    {
        $actorId = $this->actorId();

        return DB::transaction(function () use ($companyId, $payrollRunId, $handoffId, $actorId): array {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $run = PayrollRun::query()->where('company_id', $companyId)->where('id', $payrollRunId)->lockForUpdate()->first();
            if (! $run instanceof PayrollRun) {
                throw ValidationException::withMessages(['payroll_run_id' => 'The payroll run is not in this company.']);
            }
            if ($run->attendance_payroll_handoff_id !== null) {
                if ($run->attendance_payroll_handoff_id === $handoffId) {
                    return ['run' => $run, 'created' => false];
                }
                throw ValidationException::withMessages(['attendance_payroll_handoff_id' => 'This run already took a different attendance handoff.']);
            }
            if ($run->run_state !== 'in_progress' || $run->attendance_usage !== 'uses_declared_attendance') {
                throw ValidationException::withMessages(['payroll_run_id' => 'Only an in-progress run that declares attendance inputs can take a handoff.']);
            }
            $handoff = AttendancePayrollHandoff::query()->where('company_id', $companyId)->where('id', $handoffId)->lockForUpdate()->first();
            if (! $handoff instanceof AttendancePayrollHandoff || $handoff->handoff_state !== 'exported') {
                throw ValidationException::withMessages(['attendance_payroll_handoff_id' => 'Only an exported attendance handoff in this company can be taken.']);
            }
            $attendancePeriod = AttendancePeriod::query()->where('company_id', $companyId)->where('id', $handoff->attendance_period_id)->first();
            $payrollPeriod = PayrollPeriod::query()->where('company_id', $companyId)->where('id', $run->payroll_period_id)->first();
            if (! $attendancePeriod instanceof AttendancePeriod || $attendancePeriod->period_state !== 'finalized') {
                throw ValidationException::withMessages(['attendance_payroll_handoff_id' => 'The attendance period behind this handoff is not finalized.']);
            }
            if (! $payrollPeriod instanceof PayrollPeriod
                || ! $payrollPeriod->period_start->isSameDay($attendancePeriod->period_start)
                || ! $payrollPeriod->period_end->isSameDay($attendancePeriod->period_end)) {
                throw ValidationException::withMessages(['attendance_payroll_handoff_id' => 'The attendance period and the payroll period do not cover the same dates.']);
            }
            $keys = PayrollRunDeclaredInput::query()->where('payroll_run_id', $run->id)->pluck('input_key')->all();
            $lines = AttendancePayrollHandoffLine::query()->where('company_id', $companyId)->where('attendance_payroll_handoff_id', $handoff->id)->get();
            $values = $this->aggregate($lines);
            $this->reconcile($handoff, $lines, $values);
            foreach ($values as $employeeId => $employeeValues) {
                foreach ($keys as $key) {
                    PayrollRunAttendanceInput::query()->create([
                        'company_id' => $companyId,
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employeeId,
                        'input_key' => $key,
                        'copied_value' => $employeeValues[$key],
                        'attendance_payroll_handoff_id' => $handoff->id,
                    ]);
                }
            }
            $run->attendance_payroll_handoff_id = $handoff->id;
            $run->attendance_taken_at = now()->utc();
            $run->save();
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'payroll_run',
                (string) $run->id,
                'payroll_attendance_taken',
                null,
                ['attendance_payroll_handoff_id' => $handoff->id, 'employee_count' => count($values), 'input_keys' => $keys, 'acting_user_id' => $actorId],
            );

            return ['run' => $run, 'created' => true];
        });
    }

    /**
     * @param  Collection<int, AttendancePayrollHandoffLine>  $lines
     * @return array<string, array<string, int>>
     */
    private function aggregate($lines): array
    {
        $values = [];
        foreach ($lines as $line) {
            $row = $values[$line->employee_id] ?? array_fill_keys(array_keys(self::ATTENDANCE_INPUTS), 0);
            $row['attendance.day_count']++;
            $row['attendance.late_seconds'] += $line->late_seconds ?? 0;
            $key = match ($line->day_class) {
                AttendancePeriodReport::CLASS_PRESENT => 'attendance.present_days',
                AttendancePeriodReport::CLASS_INCOMPLETE => 'attendance.absent_or_incomplete_days',
                AttendancePeriodReport::CLASS_NON_WORKING => 'attendance.non_working_days',
                default => 'attendance.other_status_days',
            };
            $row[$key]++;
            $values[$line->employee_id] = $row;
        }
        ksort($values);

        return $values;
    }

    /**
     * @param  Collection<int, AttendancePayrollHandoffLine>  $lines
     * @param  array<string, array<string, int>>  $values
     */
    private function reconcile(AttendancePayrollHandoff $handoff, $lines, array $values): void
    {
        $sum = fn (string $key): int => array_sum(array_column($values, $key));
        $checks = [
            'employees' => [count($values), $handoff->employee_count],
            'days' => [$lines->count(), $handoff->line_count],
            'present' => [$sum('attendance.present_days'), $handoff->present_count],
            'absent or incomplete' => [$sum('attendance.absent_or_incomplete_days'), $handoff->incomplete_count],
            'non-working' => [$sum('attendance.non_working_days'), $handoff->non_working_count],
            'other status' => [$sum('attendance.other_status_days'), $handoff->other_status_count],
            'late seconds' => [$sum('attendance.late_seconds'), $handoff->late_seconds_total],
        ];
        $failed = [];
        foreach ($checks as $label => [$lineTotal, $headerTotal]) {
            if ($lineTotal !== $headerTotal) {
                $failed[] = 'The handoff '.$label.' total does not match its lines.';
            }
        }
        if ($failed !== []) {
            throw ValidationException::withMessages(['attendance_payroll_handoff_id' => $failed]);
        }
    }

    private function actorId(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw ValidationException::withMessages(['acting_user_id' => 'Payroll intake requires the acting user.']);
        }

        return $actorId;
    }
}
