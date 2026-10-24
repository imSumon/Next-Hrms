<?php

namespace App\Attendance;

use App\Audit\EmployeeAuditTrail;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendanceMissingPunchRequest;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\RosterSourceDay;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceDayProcessor
{
    /**
     * Calculate one day result from the company policy, the winning shift candidate,
     * the stored punch events, and any approved missing-punch time. Earlier versions
     * and raw punches stay in place.
     */
    public function calculate(string $companyId, string $employeeId, string $workDate): AttendanceDayResultVersion
    {
        return $this->run($companyId, $employeeId, $workDate, 'calculated');
    }

    public function calculateForMissingPunch(string $companyId, string $employeeId, string $workDate): AttendanceDayResultVersion
    {
        return $this->run($companyId, $employeeId, $workDate, 'missing_punch');
    }

    /**
     * The span in which a punch, or an approved missing-punch time, counts for the day.
     *
     * @return array{from: Carbon, to: Carbon}
     */
    public function window(string $companyId, string $employeeId, string $workDate): array
    {
        $policy = $this->policy($companyId);
        $this->assertEmployee($companyId, $employeeId);
        $plan = $this->plan($companyId, $employeeId, $this->workDate($workDate), $policy);

        return ['from' => $plan['from'], 'to' => $plan['to']];
    }

    /**
     * Sides where an approved missing-punch time now meets a recorded punch. The
     * day cannot be recalculated until someone decides which one counts.
     *
     * @return list<string>
     */
    public function missingPunchConflicts(string $companyId, string $employeeId, string $workDate): array
    {
        $applied = AttendanceMissingPunchRequest::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $workDate)
            ->where('request_state', 'applied')
            ->pluck('punch_side')
            ->unique()
            ->values();
        if ($applied->isEmpty()) {
            return [];
        }
        $policy = $this->policy($companyId);
        try {
            $plan = $this->plan($companyId, $employeeId, $this->workDate($workDate), $policy);
        } catch (ValidationException) {
            return [];
        }
        $selected = $this->select($this->events($companyId, $employeeId, $plan['from'], $plan['to']));

        return $applied->filter(fn (string $side): bool => $selected[$side] !== null)->values()->all();
    }

    public function policy(string $companyId): AttendancePolicy
    {
        $policy = AttendancePolicy::query()->where('company_id', $companyId)->first();
        if (! $policy instanceof AttendancePolicy) {
            throw ValidationException::withMessages([
                'company_id' => 'This company has no attendance policy.',
            ]);
        }

        return $policy;
    }

    private function run(string $companyId, string $employeeId, string $workDate, string $generationKind): AttendanceDayResultVersion
    {
        $actorId = $this->actorId();
        $policy = $this->policy($companyId);
        $this->assertEmployee($companyId, $employeeId);
        $date = $this->workDate($workDate);
        app(AttendancePeriodRegistry::class)->assertWritable($companyId, $date->toDateString());
        $plan = $this->plan($companyId, $employeeId, $date, $policy);
        $events = $this->events($companyId, $employeeId, $plan['from'], $plan['to']);
        $selected = $this->effective($companyId, $employeeId, $date, $this->select($events), $plan);
        $incomplete = $selected['in_at'] === null || $selected['out_at'] === null;
        if ($plan['source'] instanceof RosterSourceDay) {
            $evaluated = $this->evaluateWindow($policy, $plan['source'], $plan, $selected);
            $status = $evaluated['status'];
            $measurement = ['late_seconds' => $evaluated['late_seconds'], 'early_seconds' => $evaluated['early_seconds']];
        } else {
            $measurement = [
                'late_seconds' => $this->lateSeconds($policy, $plan['shift_start'], $selected['in_at']),
                'early_seconds' => $this->earlySeconds($policy, $plan['shift_end'], $selected['out_at']),
            ];
            $status = $incomplete ? $policy->incomplete_status_code : $policy->present_status_code;
        }

        return $this->persist($companyId, $employeeId, $date, $plan, $selected, $measurement, $incomplete, $status, $actorId, $generationKind);
    }

    /**
     * Resolve the shift, the punch span, and the shift boundaries for the day. The
     * margin mode takes all of them from the roster day window.
     *
     * @return array{shift_version: ShiftVersion, from: Carbon, to: Carbon, shift_start: Carbon, shift_end: Carbon, source: ?RosterSourceDay}
     */
    private function plan(string $companyId, string $employeeId, Carbon $date, AttendancePolicy $policy): array
    {
        $shiftVersion = $this->winningShift($companyId, $employeeId, $date, $policy);
        $zone = $this->zone($policy->timezone);
        if ($policy->punch_selection !== 'margin_first_and_last') {
            return $this->bounds($date, $shiftVersion, $zone) + ['shift_version' => $shiftVersion, 'source' => null];
        }
        $sources = RosterSourceDay::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $date->toDateString())
            ->get();
        if ($sources->count() !== 1) {
            throw ValidationException::withMessages([
                'work_date' => $sources->isEmpty()
                    ? 'This date has no roster day window.'
                    : 'This date has more than one roster day window.',
            ]);
        }
        $source = $sources->first();
        if ($shiftVersion->shift_id !== $source->shift_id) {
            throw ValidationException::withMessages([
                'shift_id' => 'The roster day window and the winning shift do not match.',
            ]);
        }

        return [
            'shift_version' => $shiftVersion,
            'from' => $source->window_starts_at->copy()->timezone($zone),
            'to' => $source->window_ends_at->copy()->timezone($zone),
            'shift_start' => $source->shift_starts_at->copy()->timezone($zone),
            'shift_end' => $source->shift_ends_at->copy()->timezone($zone),
            'source' => $source,
        ];
    }

    /**
     * Fill a missing side from the latest approved missing-punch request. The
     * selected raw punch ids are kept as recorded.
     *
     * @param  array{in: ?PunchEvent, out: ?PunchEvent}  $selected
     * @param  array{from: Carbon, to: Carbon}  $plan
     * @return array{in: ?PunchEvent, out: ?PunchEvent, in_at: ?Carbon, out_at: ?Carbon, in_request: ?AttendanceMissingPunchRequest, out_request: ?AttendanceMissingPunchRequest}
     */
    private function effective(string $companyId, string $employeeId, Carbon $date, array $selected, array $plan): array
    {
        $result = [
            'in' => $selected['in'],
            'out' => $selected['out'],
            'in_at' => $selected['in']?->event_at,
            'out_at' => $selected['out']?->event_at,
            'in_request' => null,
            'out_request' => null,
        ];
        $applied = AttendanceMissingPunchRequest::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $date->toDateString())
            ->where('request_state', 'applied')
            ->orderBy('decided_at')
            ->orderBy('id')
            ->get();
        foreach (['in', 'out'] as $side) {
            $request = $applied->where('punch_side', $side)->last();
            if (! $request instanceof AttendanceMissingPunchRequest) {
                continue;
            }
            if ($result[$side] !== null) {
                throw ValidationException::withMessages([
                    'punch_side' => 'An approved missing-punch time and a recorded '.$side.' punch now both exist for this day. Which one counts is not decided.',
                ]);
            }
            $at = $request->requested_event_at;
            if ($at->lessThan($plan['from']) || $at->greaterThan($plan['to'])) {
                throw ValidationException::withMessages([
                    'requested_event_at' => 'The approved missing-punch time is outside the punch window for this day.',
                ]);
            }
            $result[$side.'_at'] = $at;
            $result[$side.'_request'] = $request;
        }
        if ($result['in_at'] !== null && $result['out_at'] !== null && $result['out_at']->lessThanOrEqualTo($result['in_at'])) {
            throw ValidationException::withMessages([
                'requested_event_at' => 'The out time must be after the in time.',
            ]);
        }

        return $result;
    }

    /**
     * Classify a roster-window day: the last punch must fall between shift end and
     * the out margin, and the first punch must fall between the in margin and the
     * absent-after limit.
     *
     * @param  array{from: Carbon, to: Carbon, shift_start: Carbon, shift_end: Carbon}  $plan
     * @param  array{in_at: ?Carbon, out_at: ?Carbon}  $selected
     * @return array{status: ?string, late_seconds: ?int, early_seconds: ?int}
     */
    private function evaluateWindow(AttendancePolicy $policy, RosterSourceDay $source, array $plan, array $selected): array
    {
        $dayCode = $source->source_day_code;
        $nonWorking = $policy->non_working_day_code !== null && $dayCode === $policy->non_working_day_code;
        if (! $nonWorking && ($policy->working_day_code === null || $dayCode !== $policy->working_day_code)) {
            throw ValidationException::withMessages([
                'source_day_code' => 'The company policy has no attendance rule for this roster day code.',
            ]);
        }
        if ($nonWorking && ($policy->non_working_status_code === null || $policy->non_working_attended_status_code === null)) {
            throw ValidationException::withMessages([
                'source_day_code' => 'The company policy has no status for a non-working roster day.',
            ]);
        }
        $absentStatus = $nonWorking ? $policy->non_working_status_code : $policy->incomplete_status_code;
        $attendedStatus = $nonWorking ? $policy->non_working_attended_status_code : $policy->present_status_code;
        if ($source->always_present) {
            return ['status' => $nonWorking ? $absentStatus : $attendedStatus, 'late_seconds' => null, 'early_seconds' => null];
        }
        $in = $selected['in_at'];
        $out = $selected['out_at'];
        $leftInWindow = $out !== null && $out->greaterThanOrEqualTo($plan['shift_end']) && $out->lessThanOrEqualTo($plan['to']);
        $arrivedInWindow = $in !== null && $in->greaterThanOrEqualTo($plan['from']) && $in->lessThanOrEqualTo($source->absent_after_at);
        if (! $leftInWindow || ! $arrivedInWindow) {
            return ['status' => $absentStatus, 'late_seconds' => null, 'early_seconds' => null];
        }

        return [
            'status' => $attendedStatus,
            'late_seconds' => $this->lateSeconds($policy, $plan['shift_start'], $in),
            'early_seconds' => $this->earlySeconds($policy, $plan['shift_end'], $out),
        ];
    }

    private function zone(string $timezone): \DateTimeZone
    {
        try {
            return new \DateTimeZone($timezone);
        } catch (\Exception) {
            throw ValidationException::withMessages([
                'timezone' => 'The attendance policy timezone is not recognized.',
            ]);
        }
    }

    /**
     * @param  array{shift_version: ShiftVersion, source: ?RosterSourceDay}  $plan
     * @param  array{in: ?PunchEvent, out: ?PunchEvent, in_at: ?Carbon, out_at: ?Carbon, in_request: ?AttendanceMissingPunchRequest, out_request: ?AttendanceMissingPunchRequest}  $selected
     * @param  array{late_seconds: ?int, early_seconds: ?int}  $measurement
     */
    private function persist(string $companyId, string $employeeId, Carbon $date, array $plan, array $selected, array $measurement, bool $incomplete, ?string $status, string $actorId, string $generationKind): AttendanceDayResultVersion
    {
        return DB::transaction(function () use ($companyId, $employeeId, $date, $plan, $selected, $measurement, $incomplete, $status, $actorId, $generationKind): AttendanceDayResultVersion {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $pointer = AttendanceDayResult::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->whereDate('work_date', $date->toDateString())
                ->lockForUpdate()
                ->first();
            $current = $pointer instanceof AttendanceDayResult
                ? AttendanceDayResultVersion::query()->find($pointer->current_version_id)
                : null;
            if ($current instanceof AttendanceDayResultVersion && $current->generation_kind === 'correction') {
                throw ValidationException::withMessages([
                    'work_date' => 'This day has an approved correction. Recalculation would replace it.',
                ]);
            }
            $values = [
                'shift_version_id' => $plan['shift_version']->id,
                'status_code' => $status,
                'incomplete' => $incomplete,
                'late_seconds' => $measurement['late_seconds'],
                'early_seconds' => $measurement['early_seconds'],
                'selected_in_event_id' => $selected['in']?->id,
                'selected_out_event_id' => $selected['out']?->id,
                'roster_source_day_id' => $plan['source']?->id,
                'override_in_request_id' => $selected['in_request']?->id,
                'override_out_request_id' => $selected['out_request']?->id,
            ];
            if ($current instanceof AttendanceDayResultVersion && $this->sameCalculation($current, $values)) {
                return $current;
            }
            $version = AttendanceDayResultVersion::query()->create($values + [
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'work_date' => $date->toDateString(),
                'override_in_at' => $selected['in_request'] === null ? null : $selected['in_at']?->copy()->utc(),
                'override_out_at' => $selected['out_request'] === null ? null : $selected['out_at']?->copy()->utc(),
                'generation_kind' => $generationKind,
                'correction_id' => null,
                'previous_version_id' => $pointer?->current_version_id,
                'calculated_at' => now()->utc(),
            ]);
            if ($pointer === null) {
                AttendanceDayResult::query()->create([
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                    'work_date' => $date->toDateString(),
                    'current_version_id' => $version->id,
                ]);
            } else {
                $pointer->current_version_id = $version->id;
                $pointer->save();
            }
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_day_result_version',
                (string) $version->id,
                $generationKind === 'missing_punch' ? 'attendance_day_missing_punch_applied' : 'attendance_day_calculated',
                $pointer === null ? null : ['current_version_id' => $version->previous_version_id],
                [
                    'employee_id' => $employeeId,
                    'work_date' => $date->toDateString(),
                    'acting_user_id' => $actorId,
                    'status_code' => $status,
                    'incomplete' => $incomplete,
                ],
            );

            return $version;
        });
    }

    private function actorId(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw ValidationException::withMessages([
                'acting_user_id' => 'Attendance calculation requires the acting user.',
            ]);
        }

        return $actorId;
    }

    private function assertEmployee(string $companyId, string $employeeId): void
    {
        $exists = Employee::query()->where('company_id', $companyId)->where('id', $employeeId)->exists();
        if (! $exists) {
            throw ValidationException::withMessages([
                'employee_id' => 'The employee is not in this company.',
            ]);
        }
    }

    private function workDate(string $workDate): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
            throw ValidationException::withMessages([
                'work_date' => 'The work date must be a calendar date.',
            ]);
        }

        return Carbon::createFromFormat('Y-m-d', $workDate)->startOfDay();
    }

    private function winningShift(string $companyId, string $employeeId, Carbon $date, AttendancePolicy $policy): ShiftVersion
    {
        $query = ShiftAssignmentCandidate::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('candidate_kind', $policy->shift_winner);
        if ($policy->shift_winner === 'roster') {
            $query->whereDate('roster_work_date', $date->toDateString());
        }
        $candidate = $query->first();
        if (! $candidate instanceof ShiftAssignmentCandidate) {
            throw ValidationException::withMessages([
                'shift_winner' => 'The company shift rule has no candidate for this employee and date.',
            ]);
        }
        $version = ShiftVersion::query()
            ->where('company_id', $companyId)
            ->where('shift_id', $candidate->shift_id)
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->orderByDesc('effective_from')
            ->first();
        if (! $version instanceof ShiftVersion) {
            throw ValidationException::withMessages([
                'shift_id' => 'The winning shift has no version on this date.',
            ]);
        }

        return $version;
    }

    /**
     * @return array{from: Carbon, to: Carbon, shift_start: Carbon, shift_end: Carbon}
     */
    private function bounds(Carbon $date, ShiftVersion $version, \DateTimeZone $zone): array
    {
        $shiftStart = Carbon::parse($date->toDateString().' '.$this->clock($version->start_time), $zone);
        $shiftEnd = Carbon::parse($date->toDateString().' '.$this->clock($version->end_time), $zone);
        if ($version->crosses_midnight) {
            $shiftEnd->addDay();

            return [
                'from' => $shiftStart->copy(),
                'to' => $shiftEnd->copy(),
                'shift_start' => $shiftStart,
                'shift_end' => $shiftEnd,
            ];
        }

        return [
            'from' => Carbon::parse($date->toDateString().' 00:00:00', $zone),
            'to' => Carbon::parse($date->toDateString().' 23:59:59', $zone),
            'shift_start' => $shiftStart,
            'shift_end' => $shiftEnd,
        ];
    }

    private function clock(mixed $time): string
    {
        $text = (string) $time;
        if (preg_match('/(\d{2}:\d{2}:\d{2})/', $text, $match) === 1) {
            return $match[1];
        }

        throw ValidationException::withMessages([
            'shift_id' => 'The shift version has no clock time.',
        ]);
    }

    /**
     * @return Collection<int, PunchEvent>
     */
    private function events(string $companyId, string $employeeId, Carbon $from, Carbon $to): Collection
    {
        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->whereHas('identity', function ($query) use ($employeeId): void {
                $query->where('employee_id', $employeeId);
            })
            ->where('event_at', '>=', $from->copy()->utc())
            ->where('event_at', '<=', $to->copy()->utc())
            ->orderBy('event_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, PunchEvent>  $events
     * @return array{in: ?PunchEvent, out: ?PunchEvent}
     */
    private function select(Collection $events): array
    {
        if ($events->isEmpty()) {
            return ['in' => null, 'out' => null];
        }
        if ($events->count() === 1) {
            return ['in' => $events->first(), 'out' => null];
        }

        return ['in' => $events->first(), 'out' => $events->last()];
    }

    private function lateSeconds(AttendancePolicy $policy, Carbon $shiftStart, ?Carbon $in): ?int
    {
        if ($policy->grace_seconds === null || $policy->late_measured_from === null || $in === null) {
            return null;
        }
        $arrived = $in->copy()->timezone($shiftStart->timezone);
        $graceEnd = $shiftStart->copy()->addSeconds($policy->grace_seconds);
        if ($arrived->lessThanOrEqualTo($graceEnd)) {
            return 0;
        }
        $basis = $policy->late_measured_from === 'grace_end' ? $graceEnd : $shiftStart;

        return $arrived->getTimestamp() - $basis->getTimestamp();
    }

    private function earlySeconds(AttendancePolicy $policy, Carbon $shiftEnd, ?Carbon $out): ?int
    {
        if ($policy->early_threshold_seconds === null || $out === null) {
            return null;
        }
        $left = $out->copy()->timezone($shiftEnd->timezone);
        $limit = $shiftEnd->copy()->subSeconds($policy->early_threshold_seconds);
        if ($left->greaterThanOrEqualTo($limit)) {
            return 0;
        }

        return $shiftEnd->getTimestamp() - $left->getTimestamp();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function sameCalculation(AttendanceDayResultVersion $current, array $values): bool
    {
        foreach ($values as $key => $value) {
            if ($current->getAttribute($key) !== $value) {
                return false;
            }
        }

        return true;
    }
}
