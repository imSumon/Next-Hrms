<?php

namespace App\Attendance;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendanceMissingPunchRequest;
use App\Models\AttendancePolicy;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\ShiftAssignmentCandidate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AttendancePeriodReport
{
    public const CLASS_PRESENT = 'present';

    public const CLASS_INCOMPLETE = 'incomplete';

    public const CLASS_NON_WORKING = 'non_working';

    public const CLASS_OTHER = 'other';

    /**
     * Classify a current day-result version for summaries and the payroll handoff.
     */
    public function dayClass(AttendancePolicy $policy, AttendanceDayResultVersion $version): string
    {
        if ($policy->isNonWorkingStatus($version->status_code)) {
            return self::CLASS_NON_WORKING;
        }
        if ($version->incomplete || ($version->status_code !== null && $version->status_code === $policy->incomplete_status_code)) {
            return self::CLASS_INCOMPLETE;
        }
        if ($version->status_code !== null && $version->status_code === $policy->present_status_code) {
            return self::CLASS_PRESENT;
        }

        return self::CLASS_OTHER;
    }

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     employees: list<array<string, mixed>>,
     *     totals: array<string, int>,
     *     reconciled: bool,
     *     exceptions: list<array<string, mixed>>,
     *     missing_results: list<array{employee_id: string, employee_code: ?string, work_date: string}>,
     *     pending_corrections: list<array{id: string, employee_id: string, work_date: string}>,
     *     pending_missing_punch_requests: list<array{id: string, employee_id: string, work_date: string, punch_side: string}>,
     *     missing_punch_conflicts: list<array{employee_id: string, work_date: string, punch_sides: list<string>}>,
     *     unprocessed_punches: list<array{employee_id: string, work_date: string, event_count: int}>,
     *     raw_event_count: int,
     *     selected_event_count: int
     * }
     */
    public function build(string $companyId, string $from, string $to): array
    {
        $this->assertRange($from, $to);
        $policy = app(AttendanceDayProcessor::class)->policy($companyId);
        $results = $this->results($companyId, $from, $to);
        $employees = Employee::query()->where('company_id', $companyId)->get()->keyBy('id');
        $expected = $this->expectedDays($companyId, $policy, $from, $to);
        $resultKeys = [];
        $rows = [];
        $exceptions = [];
        $selected = [];
        $totals = ['expected' => count($expected), 'processed' => 0, self::CLASS_PRESENT => 0, self::CLASS_INCOMPLETE => 0, self::CLASS_NON_WORKING => 0, self::CLASS_OTHER => 0, 'late_seconds' => 0];
        foreach ($results as $result) {
            $version = $result->currentVersion;
            if (! $version instanceof AttendanceDayResultVersion) {
                continue;
            }
            $workDate = $result->work_date->toDateString();
            $resultKeys[$result->employee_id.'|'.$workDate] = true;
            $class = $this->dayClass($policy, $version);
            $row = $rows[$result->employee_id] ?? $this->emptyRow($result->employee_id, $employees->get($result->employee_id));
            $row['processed']++;
            $row[$class]++;
            $row['late_seconds'] += $version->late_seconds ?? 0;
            if ($version->generation_kind === 'correction') {
                $row['corrected']++;
            }
            $rows[$result->employee_id] = $row;
            $totals['processed']++;
            $totals[$class]++;
            $totals['late_seconds'] += $version->late_seconds ?? 0;
            foreach ([$version->selected_in_event_id, $version->selected_out_event_id] as $eventId) {
                if ($eventId !== null) {
                    $selected[$eventId] = true;
                }
            }
            if ($class === self::CLASS_INCOMPLETE) {
                $exceptions[] = [
                    'employee_id' => $result->employee_id,
                    'employee_code' => $employees->get($result->employee_id)?->employee_code,
                    'work_date' => $workDate,
                    'status_code' => $version->status_code,
                    'incomplete' => $version->incomplete,
                    'missing' => $version->selected_in_event_id === null ? 'in_and_out' : ($version->selected_out_event_id === null ? 'out' : 'none'),
                ];
            }
        }
        $missing = [];
        foreach ($expected as $key => $day) {
            if (! isset($resultKeys[$key])) {
                $missing[] = [
                    'employee_id' => $day['employee_id'],
                    'employee_code' => $employees->get($day['employee_id'])?->employee_code,
                    'work_date' => $day['work_date'],
                ];
            }
            $row = $rows[$day['employee_id']] ?? $this->emptyRow($day['employee_id'], $employees->get($day['employee_id']));
            $row['expected']++;
            $rows[$day['employee_id']] = $row;
        }
        $events = $this->events($companyId, $policy, $from, $to);
        ksort($rows);

        return [
            'from' => $from,
            'to' => $to,
            'employees' => array_values($rows),
            'totals' => $totals,
            'reconciled' => $totals['processed'] === $totals[self::CLASS_PRESENT] + $totals[self::CLASS_INCOMPLETE] + $totals[self::CLASS_NON_WORKING] + $totals[self::CLASS_OTHER],
            'exceptions' => $exceptions,
            'missing_results' => $missing,
            'pending_corrections' => $this->pendingCorrections($companyId, $from, $to),
            'pending_missing_punch_requests' => $this->pendingMissingPunchRequests($companyId, $from, $to),
            'missing_punch_conflicts' => $this->missingPunchConflicts($companyId, $from, $to),
            'unprocessed_punches' => $this->unprocessedPunches($events, $resultKeys, $policy),
            'raw_event_count' => $events->count(),
            'selected_event_count' => count($selected),
        ];
    }

    /**
     * @return Collection<int, AttendanceDayResult>
     */
    public function results(string $companyId, string $from, string $to): Collection
    {
        return AttendanceDayResult::query()
            ->where('company_id', $companyId)
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->with('currentVersion')
            ->orderBy('work_date')
            ->orderBy('employee_id')
            ->get();
    }

    public function assertRange(string $from, string $to): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || $to < $from) {
            throw ValidationException::withMessages([
                'period_end' => 'The range needs a start date and an end date on or after it.',
            ]);
        }
    }

    /**
     * Employee-days the company shift rule expects a result for.
     *
     * @return array<string, array{employee_id: string, work_date: string}>
     */
    private function expectedDays(string $companyId, AttendancePolicy $policy, string $from, string $to): array
    {
        $expected = [];
        if ($policy->shift_winner === 'roster') {
            $candidates = ShiftAssignmentCandidate::query()
                ->where('company_id', $companyId)
                ->where('candidate_kind', 'roster')
                ->whereDate('roster_work_date', '>=', $from)
                ->whereDate('roster_work_date', '<=', $to)
                ->get();
            foreach ($candidates as $candidate) {
                $workDate = Carbon::parse($candidate->roster_work_date)->toDateString();
                $expected[$candidate->employee_id.'|'.$workDate] = ['employee_id' => $candidate->employee_id, 'work_date' => $workDate];
            }
            ksort($expected);

            return $expected;
        }
        $employeeIds = ShiftAssignmentCandidate::query()
            ->where('company_id', $companyId)
            ->where('candidate_kind', 'default')
            ->pluck('employee_id');
        $last = Carbon::parse($to);
        foreach ($employeeIds as $employeeId) {
            for ($day = Carbon::parse($from); $day->lessThanOrEqualTo($last); $day->addDay()) {
                $expected[$employeeId.'|'.$day->toDateString()] = ['employee_id' => $employeeId, 'work_date' => $day->toDateString()];
            }
        }
        ksort($expected);

        return $expected;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRow(string $employeeId, ?Employee $employee): array
    {
        return [
            'employee_id' => $employeeId,
            'employee_code' => $employee?->employee_code,
            'display_name' => $employee?->display_name,
            'expected' => 0,
            'processed' => 0,
            self::CLASS_PRESENT => 0,
            self::CLASS_INCOMPLETE => 0,
            self::CLASS_NON_WORKING => 0,
            self::CLASS_OTHER => 0,
            'late_seconds' => 0,
            'corrected' => 0,
        ];
    }

    /**
     * @return list<array{id: string, employee_id: string, work_date: string}>
     */
    private function pendingCorrections(string $companyId, string $from, string $to): array
    {
        return AttendanceCorrection::query()
            ->where('company_id', $companyId)
            ->where('correction_state', 'submitted')
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->orderBy('work_date')
            ->get()
            ->map(fn (AttendanceCorrection $correction): array => [
                'id' => (string) $correction->id,
                'employee_id' => $correction->employee_id,
                'work_date' => $correction->work_date->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{employee_id: string, work_date: string, punch_sides: list<string>}>
     */
    private function missingPunchConflicts(string $companyId, string $from, string $to): array
    {
        $days = AttendanceMissingPunchRequest::query()
            ->where('company_id', $companyId)
            ->where('request_state', 'applied')
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->get()
            ->map(fn (AttendanceMissingPunchRequest $request): string => $request->employee_id.'|'.$request->work_date->toDateString())
            ->unique()
            ->sort()
            ->values();
        $processor = app(AttendanceDayProcessor::class);
        $conflicts = [];
        foreach ($days as $day) {
            [$employeeId, $workDate] = explode('|', $day);
            $sides = $processor->missingPunchConflicts($companyId, $employeeId, $workDate);
            if ($sides !== []) {
                $conflicts[] = ['employee_id' => $employeeId, 'work_date' => $workDate, 'punch_sides' => $sides];
            }
        }

        return $conflicts;
    }

    /**
     * @return list<array{id: string, employee_id: string, work_date: string, punch_side: string}>
     */
    private function pendingMissingPunchRequests(string $companyId, string $from, string $to): array
    {
        return AttendanceMissingPunchRequest::query()
            ->where('company_id', $companyId)
            ->where('request_state', 'submitted')
            ->whereDate('work_date', '>=', $from)
            ->whereDate('work_date', '<=', $to)
            ->orderBy('work_date')
            ->get()
            ->map(fn (AttendanceMissingPunchRequest $request): array => [
                'id' => (string) $request->id,
                'employee_id' => $request->employee_id,
                'work_date' => $request->work_date->toDateString(),
                'punch_side' => $request->punch_side,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, PunchEvent>
     */
    private function events(string $companyId, AttendancePolicy $policy, string $from, string $to): Collection
    {
        $zone = new \DateTimeZone($policy->timezone);

        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->where('event_at', '>=', Carbon::parse($from.' 00:00:00', $zone)->utc())
            ->where('event_at', '<=', Carbon::parse($to.' 23:59:59', $zone)->utc())
            ->with('identity')
            ->orderBy('event_at')
            ->get();
    }

    /**
     * Punch days with no result on that date or the previous date, which a night shift could own.
     *
     * @param  Collection<int, PunchEvent>  $events
     * @param  array<string, bool>  $resultKeys
     * @return list<array{employee_id: string, work_date: string, event_count: int}>
     */
    private function unprocessedPunches(Collection $events, array $resultKeys, AttendancePolicy $policy): array
    {
        $zone = new \DateTimeZone($policy->timezone);
        $days = [];
        foreach ($events as $event) {
            $employeeId = $event->identity?->employee_id;
            if ($employeeId === null) {
                continue;
            }
            $local = $event->event_at->copy()->timezone($zone);
            $date = $local->toDateString();
            $previous = $local->copy()->subDay()->toDateString();
            if (isset($resultKeys[$employeeId.'|'.$date]) || isset($resultKeys[$employeeId.'|'.$previous])) {
                continue;
            }
            $key = $employeeId.'|'.$date;
            $days[$key] = [
                'employee_id' => $employeeId,
                'work_date' => $date,
                'event_count' => ($days[$key]['event_count'] ?? 0) + 1,
            ];
        }
        ksort($days);

        return array_values($days);
    }
}
