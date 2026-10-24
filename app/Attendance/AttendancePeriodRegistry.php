<?php

namespace App\Attendance;

use App\Audit\EmployeeAuditTrail;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePayrollHandoff;
use App\Models\AttendancePayrollHandoffLine;
use App\Models\AttendancePeriod;
use App\Models\AttendancePeriodEvent;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendancePeriodRegistry
{
    public function __construct(private AttendancePeriodReport $report) {}

    public function assertWritable(string $companyId, string $workDate): void
    {
        $closed = AttendancePeriod::query()
            ->where('company_id', $companyId)
            ->where('period_state', 'finalized')
            ->whereDate('period_start', '<=', $workDate)
            ->whereDate('period_end', '>=', $workDate)
            ->exists();
        if ($closed) {
            throw ValidationException::withMessages([
                'work_date' => 'This attendance period is finalized. Reopen it before changing results.',
            ]);
        }
    }

    public function open(string $companyId, string $periodStart, string $periodEnd): AttendancePeriod
    {
        $actorId = $this->actorId();
        $this->report->assertRange($periodStart, $periodEnd);
        app(AttendanceDayProcessor::class)->policy($companyId);

        return DB::transaction(function () use ($companyId, $periodStart, $periodEnd, $actorId): AttendancePeriod {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            DB::select('select pg_advisory_xact_lock(hashtext(?)::bigint)', ['attendance-period|'.$companyId]);
            $overlap = AttendancePeriod::query()
                ->where('company_id', $companyId)
                ->whereDate('period_start', '<=', $periodEnd)
                ->whereDate('period_end', '>=', $periodStart)
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages([
                    'period_start' => 'An attendance period already covers these dates.',
                ]);
            }
            $period = AttendancePeriod::query()->create([
                'company_id' => $companyId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'period_state' => 'open',
            ]);
            $this->event($companyId, $period->id, 'opened', $actorId, null);

            return $period;
        });
    }

    public function finalize(string $companyId, string $periodId): AttendancePeriod
    {
        $actorId = $this->actorId();

        return DB::transaction(function () use ($companyId, $periodId, $actorId): AttendancePeriod {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $period = $this->locked($companyId, $periodId);
            if ($period->period_state !== 'open') {
                throw ValidationException::withMessages([
                    'period_state' => 'Only an open attendance period can be finalized.',
                ]);
            }
            $blockers = $this->finalizationBlockers($this->report->build($companyId, $period->period_start->toDateString(), $period->period_end->toDateString()));
            if ($blockers !== []) {
                throw ValidationException::withMessages(['period_state' => $blockers]);
            }
            $period->period_state = 'finalized';
            $period->finalized_by_user_id = $actorId;
            $period->finalized_at = now()->utc();
            $period->save();
            $this->event($companyId, $period->id, 'finalized', $actorId, null);
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_period',
                (string) $period->id,
                'attendance_period_finalized',
                ['period_state' => 'open'],
                ['period_state' => 'finalized', 'acting_user_id' => $actorId],
            );

            return $period;
        });
    }

    /**
     * @param  array<string, mixed>  $report  The output of AttendancePeriodReport::build for the period.
     * @return list<string>
     */
    public function finalizationBlockers(array $report): array
    {
        $blockers = [];
        if ($report['totals']['processed'] === 0) {
            $blockers[] = 'This period has no attendance results.';
        }
        if ($report['missing_results'] !== []) {
            $blockers[] = count($report['missing_results']).' expected employee-days have no result.';
        }
        if ($report['pending_corrections'] !== []) {
            $blockers[] = count($report['pending_corrections']).' corrections are still waiting for a decision.';
        }
        if ($report['pending_missing_punch_requests'] !== []) {
            $blockers[] = count($report['pending_missing_punch_requests']).' missing-punch requests are still waiting for review.';
        }
        if ($report['missing_punch_conflicts'] !== []) {
            $blockers[] = count($report['missing_punch_conflicts']).' days have an approved missing-punch time and a later recorded punch on the same side.';
        }
        if ($report['unprocessed_punches'] !== []) {
            $blockers[] = count($report['unprocessed_punches']).' employee-days have punches but no result.';
        }

        return $blockers;
    }

    public function reopen(string $companyId, string $periodId, string $reason): AttendancePeriod
    {
        $actorId = $this->actorId();
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Reopening a finalized period needs a reason.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $periodId, $actorId, $reason): AttendancePeriod {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $period = $this->locked($companyId, $periodId);
            if ($period->period_state !== 'finalized') {
                throw ValidationException::withMessages([
                    'period_state' => 'Only a finalized attendance period can be reopened.',
                ]);
            }
            $superseded = AttendancePayrollHandoff::query()
                ->where('company_id', $companyId)
                ->where('attendance_period_id', $period->id)
                ->where('handoff_state', 'exported')
                ->get();
            $runs = PayrollRun::query()
                ->where('company_id', $companyId)
                ->whereIn('attendance_payroll_handoff_id', $superseded->pluck('id'))
                ->lockForUpdate()
                ->get();
            if ($runs->contains(fn (PayrollRun $run): bool => $run->run_state === 'finalized')) {
                throw ValidationException::withMessages([
                    'period_state' => 'A finalized payroll run used this attendance handoff. Reopen payroll first.',
                ]);
            }
            foreach ($runs->whereIn('run_state', ['in_progress', 'draft']) as $run) {
                $run->run_state = 'superseded';
                $run->save();
                app(EmployeeAuditTrail::class)->record(
                    $companyId,
                    'payroll_run',
                    (string) $run->id,
                    'payroll_run_superseded_by_attendance_reopen',
                    ['run_state' => 'in_progress'],
                    ['run_state' => 'superseded', 'reason' => $reason],
                );
            }
            foreach ($superseded as $handoff) {
                $handoff->handoff_state = 'superseded';
                $handoff->save();
                app(EmployeeAuditTrail::class)->record(
                    $companyId,
                    'attendance_payroll_handoff',
                    (string) $handoff->id,
                    'attendance_payroll_handoff_superseded',
                    ['handoff_state' => 'exported'],
                    ['handoff_state' => 'superseded', 'reason' => $reason],
                );
            }
            $period->period_state = 'open';
            $period->reopened_by_user_id = $actorId;
            $period->reopened_at = now()->utc();
            $period->save();
            $this->event($companyId, $period->id, 'reopened', $actorId, $reason);
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_period',
                (string) $period->id,
                'attendance_period_reopened',
                ['period_state' => 'finalized'],
                ['period_state' => 'open', 'reason' => $reason, 'acting_user_id' => $actorId],
            );

            return $period;
        });
    }

    /**
     * Hand the finalized period to payroll once. A repeat returns the existing handoff.
     *
     * @return array{handoff: AttendancePayrollHandoff, created: bool}
     */
    public function export(string $companyId, string $periodId): array
    {
        $actorId = $this->actorId();

        return DB::transaction(function () use ($companyId, $periodId, $actorId): array {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $period = $this->locked($companyId, $periodId);
            if ($period->period_state !== 'finalized') {
                throw ValidationException::withMessages([
                    'period_state' => 'Payroll handoff requires a finalized attendance period.',
                ]);
            }
            $existing = AttendancePayrollHandoff::query()
                ->where('company_id', $companyId)
                ->where('attendance_period_id', $period->id)
                ->where('handoff_state', 'exported')
                ->first();
            if ($existing instanceof AttendancePayrollHandoff) {
                return ['handoff' => $existing, 'created' => false];
            }
            $policy = app(AttendanceDayProcessor::class)->policy($companyId);
            $results = $this->report->results($companyId, $period->period_start->toDateString(), $period->period_end->toDateString());
            $counts = [
                AttendancePeriodReport::CLASS_PRESENT => 0,
                AttendancePeriodReport::CLASS_INCOMPLETE => 0,
                AttendancePeriodReport::CLASS_NON_WORKING => 0,
                AttendancePeriodReport::CLASS_OTHER => 0,
            ];
            $late = 0;
            $lines = [];
            foreach ($results as $result) {
                $version = $result->currentVersion;
                if (! $version instanceof AttendanceDayResultVersion) {
                    throw ValidationException::withMessages([
                        'work_date' => 'A day result in this period has no current version.',
                    ]);
                }
                $class = $this->report->dayClass($policy, $version);
                $counts[$class]++;
                $late += $version->late_seconds ?? 0;
                $lines[] = [$result, $version, $class];
            }
            $handoff = AttendancePayrollHandoff::query()->create([
                'company_id' => $companyId,
                'attendance_period_id' => $period->id,
                'handoff_state' => 'exported',
                'exported_by_user_id' => $actorId,
                'exported_at' => now()->utc(),
                'employee_count' => $results->pluck('employee_id')->unique()->count(),
                'line_count' => $results->count(),
                'present_count' => $counts[AttendancePeriodReport::CLASS_PRESENT],
                'incomplete_count' => $counts[AttendancePeriodReport::CLASS_INCOMPLETE],
                'non_working_count' => $counts[AttendancePeriodReport::CLASS_NON_WORKING],
                'other_status_count' => $counts[AttendancePeriodReport::CLASS_OTHER],
                'late_seconds_total' => $late,
            ]);
            foreach ($lines as [$result, $version, $class]) {
                AttendancePayrollHandoffLine::query()->create([
                    'company_id' => $companyId,
                    'attendance_payroll_handoff_id' => $handoff->id,
                    'employee_id' => $result->employee_id,
                    'work_date' => $result->work_date->toDateString(),
                    'result_version_id' => $version->id,
                    'day_class' => $class,
                    'status_code' => $version->status_code,
                    'incomplete' => $version->incomplete,
                    'late_seconds' => $version->late_seconds,
                    'early_seconds' => $version->early_seconds,
                ]);
            }
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_payroll_handoff',
                (string) $handoff->id,
                'attendance_payroll_handoff_exported',
                null,
                ['attendance_period_id' => $period->id, 'line_count' => $handoff->line_count, 'employee_count' => $handoff->employee_count],
            );

            return ['handoff' => $handoff, 'created' => true];
        });
    }

    private function actorId(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw ValidationException::withMessages([
                'acting_user_id' => 'Attendance period changes require the acting user.',
            ]);
        }

        return $actorId;
    }

    private function locked(string $companyId, string $periodId): AttendancePeriod
    {
        $period = AttendancePeriod::query()
            ->where('company_id', $companyId)
            ->where('id', $periodId)
            ->lockForUpdate()
            ->first();
        if (! $period instanceof AttendancePeriod) {
            throw ValidationException::withMessages([
                'attendance_period_id' => 'The attendance period is not in this company.',
            ]);
        }

        return $period;
    }

    private function event(string $companyId, string $periodId, string $kind, string $actorId, ?string $reason): void
    {
        AttendancePeriodEvent::query()->create([
            'company_id' => $companyId,
            'attendance_period_id' => $periodId,
            'event_kind' => $kind,
            'acting_user_id' => $actorId,
            'reason' => $reason,
            'occurred_at' => now()->utc(),
        ]);
    }
}
