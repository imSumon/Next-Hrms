<?php

namespace App\Attendance;

use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePeriod;
use App\Models\AttendancePolicy;
use App\Models\PunchEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operational summary for one company and one work date. Day figures come from
 * stored punches and current result versions; nothing is calculated here, and an
 * employee-day without a result is never counted as absent.
 */
class AttendanceDashboard
{
    public function __construct(
        private AttendancePeriodReport $report,
        private AttendancePeriodRegistry $periods,
        private DeviceLogSource $source,
        private DeviceLogPreview $preview,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(string $companyId, ?string $workDate): array
    {
        $policy = AttendancePolicy::query()->where('company_id', $companyId)->first();
        if (! $policy instanceof AttendancePolicy) {
            return [
                'policy_configured' => false,
                'work_date' => $workDate,
                'source' => $this->source->describe() + ['status' => 'not_checked'],
                'ingestion' => $this->ingestion($companyId),
            ];
        }
        $zone = new \DateTimeZone($policy->timezone);
        $date = $workDate ?? Carbon::now($zone)->toDateString();
        $this->report->assertRange($date, $date);
        $day = $this->report->build($companyId, $date, $date);
        $punchedEmployees = $this->punchedEmployees($companyId, $date, $zone);
        $results = $this->resultBreakdown($policy, $this->report->results($companyId, $date, $date));
        $noPunch = collect($day['missing_results'])->reject(fn (array $row): bool => in_array($row['employee_id'], $punchedEmployees, true))->count();
        [$rangeFrom, $rangeTo, $period] = $this->queueRange($companyId, $date);
        $queueFrom = $rangeFrom;
        $queueTo = min($rangeTo, $date);
        $queue = $this->report->build($companyId, $queueFrom, $queueTo);
        $blockers = $period instanceof AttendancePeriod && $period->period_state === 'open'
            ? $this->periods->finalizationBlockers($this->report->build($companyId, $rangeFrom, $rangeTo))
            : [];

        return [
            'policy_configured' => true,
            'timezone' => $policy->timezone,
            'work_date' => $date,
            'day' => [
                'expected_employee_days' => $day['totals']['expected'],
                'employees_with_punches' => count($punchedEmployees),
                'raw_punch_events' => $day['raw_event_count'],
                'results_calculated' => $day['totals']['processed'],
                'expected_without_result' => count($day['missing_results']),
                'no_punch_received' => $noPunch,
                'awaiting_processing' => count($day['unprocessed_punches']),
            ] + $results,
            'queue' => [
                'from' => $queueFrom,
                'to' => $queueTo,
                'basis' => $period instanceof AttendancePeriod ? 'attendance_period' : 'calendar_month',
                'pending_missing_punch_requests' => count($queue['pending_missing_punch_requests']),
                'pending_corrections' => count($queue['pending_corrections']),
                'missing_punch_conflicts' => count($queue['missing_punch_conflicts']),
                'expected_without_result' => count($queue['missing_results']),
                'awaiting_processing' => count($queue['unprocessed_punches']),
            ],
            'period' => $period instanceof AttendancePeriod ? [
                'id' => $period->id,
                'period_start' => $period->period_start->toDateString(),
                'period_end' => $period->period_end->toDateString(),
                'period_state' => $period->period_state,
                'finalization_blockers' => $blockers,
            ] : null,
            'source' => $this->sourceSummary($companyId, $date),
            'ingestion' => $this->ingestion($companyId),
        ];
    }

    /**
     * @return list<string>
     */
    private function punchedEmployees(string $companyId, string $date, \DateTimeZone $zone): array
    {
        return PunchEvent::query()
            ->join('punch_identities', function ($join): void {
                $join->on('punch_identities.company_id', '=', 'punch_events.company_id')
                    ->on('punch_identities.id', '=', 'punch_events.punch_identity_id');
            })
            ->where('punch_events.company_id', $companyId)
            ->where('punch_events.event_at', '>=', Carbon::parse($date.' 00:00:00', $zone)->utc())
            ->where('punch_events.event_at', '<=', Carbon::parse($date.' 23:59:59', $zone)->utc())
            ->distinct()
            ->pluck('punch_identities.employee_id')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AttendanceDayResult>  $results
     * @return array<string, int>
     */
    private function resultBreakdown(AttendancePolicy $policy, $results): array
    {
        $counts = [
            'present' => 0,
            'calculated_absent' => 0,
            'incomplete' => 0,
            'non_working' => 0,
            'other_status' => 0,
            'late' => 0,
            'late_not_calculated' => 0,
            'early_departure' => 0,
            'early_not_calculated' => 0,
            'approved_corrections' => 0,
            'approved_missing_punch_times' => 0,
        ];
        foreach ($results as $result) {
            $version = $result->currentVersion;
            if (! $version instanceof AttendanceDayResultVersion) {
                continue;
            }
            $hasIn = $version->selected_in_event_id !== null || $version->override_in_at !== null;
            $hasOut = $version->selected_out_event_id !== null || $version->override_out_at !== null;
            $class = $this->report->dayClass($policy, $version);
            $key = match (true) {
                $class === AttendancePeriodReport::CLASS_PRESENT => 'present',
                $class === AttendancePeriodReport::CLASS_NON_WORKING => 'non_working',
                $class === AttendancePeriodReport::CLASS_OTHER => 'other_status',
                $hasIn xor $hasOut => 'incomplete',
                default => 'calculated_absent',
            };
            $counts[$key]++;
            if ($version->late_seconds === null) {
                $counts['late_not_calculated']++;
            } elseif ($version->late_seconds > 0) {
                $counts['late']++;
            }
            if ($version->early_seconds === null) {
                $counts['early_not_calculated']++;
            } elseif ($version->early_seconds > 0) {
                $counts['early_departure']++;
            }
            $counts['approved_corrections'] += $version->generation_kind === 'correction' ? 1 : 0;
            $counts['approved_missing_punch_times'] += $version->override_in_at !== null || $version->override_out_at !== null ? 1 : 0;
        }

        return $counts;
    }

    /**
     * The attendance period covering the date, or the calendar month when none does.
     *
     * @return array{0: string, 1: string, 2: ?AttendancePeriod}
     */
    private function queueRange(string $companyId, string $date): array
    {
        $period = AttendancePeriod::query()
            ->where('company_id', $companyId)
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->first();
        if ($period instanceof AttendancePeriod) {
            return [$period->period_start->toDateString(), $period->period_end->toDateString(), $period];
        }
        $month = Carbon::parse($date);

        return [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString(), null];
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceSummary(string $companyId, string $date): array
    {
        $describe = $this->source->describe();
        if (! $describe['configured']) {
            return $describe + ['status' => 'not_configured'];
        }
        try {
            $preview = $this->preview->build($companyId, $date, $date);
        } catch (ValidationException $exception) {
            return $describe + ['status' => 'refused', 'message' => collect($exception->errors())->flatten()->first()];
        } catch (\Throwable) {
            return $describe + ['status' => 'unavailable', 'message' => 'The device log source could not be read.'];
        }

        return $describe + [
            'status' => 'read',
            'row_count' => $preview['row_count'],
            'truncated' => $preview['truncated'],
            'counts' => $preview['counts'],
            'writes_enabled' => false,
        ];
    }

    /**
     * Latest stored punch per named source. Failed imports store nothing, so they
     * cannot be listed here.
     *
     * @return list<array{source_name: string, events: int, latest_ingested_at: ?string, latest_event_at: ?string}>
     */
    private function ingestion(string $companyId): array
    {
        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->groupBy('source_name')
            ->orderBy('source_name')
            ->get(['source_name', DB::raw('count(*) as events'), DB::raw('max(ingested_at) as latest_ingested_at'), DB::raw('max(event_at) as latest_event_at')])
            ->map(fn (PunchEvent $row): array => [
                'source_name' => $row->source_name,
                'events' => (int) $row->getAttribute('events'),
                'latest_ingested_at' => $row->getAttribute('latest_ingested_at') === null ? null : Carbon::parse($row->getAttribute('latest_ingested_at'))->toIso8601String(),
                'latest_event_at' => $row->getAttribute('latest_event_at') === null ? null : Carbon::parse($row->getAttribute('latest_event_at'))->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
