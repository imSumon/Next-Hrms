<?php

namespace App\Attendance;

use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Punch\DeviceEventIngestion;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Classifies legacy device-log rows for one company without writing anything.
 * A card maps only through an identity the company already holds; nothing is
 * inferred from the shared log, which has no company column.
 */
class DeviceLogPreview
{
    public const IMPORTABLE = 'importable';

    public const ALREADY_RECORDED = 'already_recorded';

    public const POSSIBLE_DUPLICATE = 'possible_duplicate';

    public const UNMAPPED = 'unmapped';

    public const AMBIGUOUS = 'ambiguous';

    public const INVALID_TIMESTAMP = 'invalid_timestamp';

    public const INVALID_IDENTITY = 'invalid_identity';

    public const INVALID_SOURCE_EVENT = 'invalid_source_event';

    /**
     * Decisions that keep the preview from storing anything.
     *
     * @var list<string>
     */
    public const BLOCKING_DECISIONS = [
        'PUNCH-CONF-002: approve how a repeated source row (same source name and source event id) and a same-time event from another source are treated. Until then a batch containing either is refused.',
        'Confirm that all_att_log.date is wall-clock time in the company attendance policy timezone. The column stores no zone.',
        'Name who registers card numbers under Card identities for each company, and which company card map is the evidence.',
        'Authorize a live, read-only connection to the legacy master database. Only a 2026-10-02 dump has been inspected.',
    ];

    public function __construct(private DeviceLogSource $source, private AttendanceDayProcessor $processor) {}

    /**
     * @return array<string, mixed>
     */
    public function build(string $companyId, string $fromDate, string $toDate): array
    {
        app(AttendancePeriodReport::class)->assertRange($fromDate, $toDate);
        if (Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate)) > 31) {
            throw ValidationException::withMessages(['to' => 'Preview at most 31 days at a time.']);
        }
        $policy = $this->processor->policy($companyId);
        $zone = new \DateTimeZone($policy->timezone);
        $limit = max(1, (int) config('attendance.device_log.max_rows'));
        $read = $this->source->read($fromDate.' 00:00:00', $toDate.' 23:59:59', $limit);
        $now = now()->utc();
        $sourceName = (string) config('attendance.device_log.source_name');

        $codes = collect($read['rows'])->pluck('emp_code')->map(fn (?string $code): string => trim((string) $code))->filter()->unique()->values();
        $identities = PunchIdentity::query()
            ->where('company_id', $companyId)
            ->whereIn('identity_value', $codes)
            ->get()
            ->groupBy('identity_value');
        $elsewhere = PunchIdentity::query()
            ->where('company_id', '!=', $companyId)
            ->whereIn('identity_value', $codes)
            ->get()
            ->groupBy('identity_value');
        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $identities->flatten()->pluck('employee_id')->unique())
            ->get()
            ->keyBy('id');
        $recorded = PunchEvent::query()
            ->where('company_id', $companyId)
            ->where('source_name', $sourceName)
            ->whereIn('source_event_id', collect($read['rows'])->map(fn (array $row): ?string => DeviceEventIngestion::sourceEventIdFromRowId($row['id']))->filter())
            ->pluck('source_event_id')
            ->flip();

        $rows = [];
        $counts = array_fill_keys([self::IMPORTABLE, self::ALREADY_RECORDED, self::POSSIBLE_DUPLICATE, self::UNMAPPED, self::AMBIGUOUS, self::INVALID_TIMESTAMP, self::INVALID_IDENTITY, self::INVALID_SOURCE_EVENT], 0);
        foreach ($read['rows'] as $row) {
            $code = trim((string) $row['emp_code']);
            $sourceEventId = DeviceEventIngestion::sourceEventIdFromRowId($row['id']);
            $line = [
                'source_event_id' => $sourceEventId ?? DeviceEventIngestion::sourceEventPrefix().$row['id'],
                'source_wall_time' => $row['date'],
                'event_at' => null,
                'identity_value' => $code === '' ? null : $code,
                'employee_id' => null,
                'employee_code' => null,
                'source_area' => $row['area'],
            ];
            $instant = $this->instant($row['date'], $zone);
            [$outcome, $message] = match (true) {
                $sourceEventId === null => [self::INVALID_SOURCE_EVENT, 'The source event id must be all_att_log:{id} where {id} is a positive integer with no leading zeros.'],
                $code === '' => [self::INVALID_IDENTITY, 'The source row has no card number.'],
                $instant === null => [self::INVALID_TIMESTAMP, 'The source time cannot be read as a date and time.'],
                $instant->greaterThan($now) => [self::INVALID_TIMESTAMP, 'The source time is later than now.'],
                default => [null, null],
            };
            if ($outcome === null) {
                $line['event_at'] = $instant->toIso8601String();
                $covering = ($identities->get($code) ?? collect())->filter(fn (PunchIdentity $identity): bool => $this->covers($identity, $instant));
                $coveringElsewhere = ($elsewhere->get($code) ?? collect())->filter(fn (PunchIdentity $identity): bool => $this->covers($identity, $instant));
                if ($covering->pluck('employee_id')->unique()->count() > 1) {
                    [$outcome, $message] = [self::AMBIGUOUS, 'This card is active for more than one employee in this company at that time. Review it under Card identities and end the mapping that does not apply.'];
                } elseif ($covering->isNotEmpty() && $coveringElsewhere->isNotEmpty()) {
                    [$outcome, $message] = [self::AMBIGUOUS, 'This card is also registered in another company. The shared log has no company column, so the row cannot be attributed until only one company holds the card at that time.'];
                } elseif ($covering->isEmpty()) {
                    [$outcome, $message] = [self::UNMAPPED, 'No punch identity in this company holds this card at that time. Register the card under Card identities if it belongs to an employee of this company.'];
                } else {
                    $identity = $covering->first();
                    $employee = $employees->get($identity->employee_id);
                    $line['employee_id'] = $identity->employee_id;
                    $line['employee_code'] = $employee?->employee_code;
                    if ($recorded->has($sourceEventId)) {
                        [$outcome, $message] = [self::ALREADY_RECORDED, 'This source row is already a punch event in this company.'];
                    } elseif ($this->sameTimeEventExists($companyId, $code, $instant)) {
                        [$outcome, $message] = [self::POSSIBLE_DUPLICATE, 'A punch for this card at the same time is already recorded from another source or row.'];
                    } else {
                        [$outcome, $message] = [self::IMPORTABLE, null];
                    }
                }
            }
            $counts[$outcome]++;
            $rows[] = $line + ['outcome' => $outcome, 'message' => $message];
        }

        return [
            'source' => $this->source->describe(),
            'from' => $fromDate,
            'to' => $toDate,
            'time_basis' => 'Source wall time read in the company attendance policy timezone '.$policy->timezone.'.',
            'row_count' => count($rows),
            'truncated' => $read['truncated'],
            'max_rows' => $limit,
            'counts' => $counts,
            'rows' => $rows,
            'writes_enabled' => false,
            'blocking_decisions' => self::BLOCKING_DECISIONS,
        ];
    }

    private function instant(?string $wallTime, \DateTimeZone $zone): ?Carbon
    {
        if ($wallTime === null || ! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $wallTime) || str_starts_with($wallTime, '0000')) {
            return null;
        }
        $parsed = Carbon::createFromFormat('Y-m-d H:i:s', $wallTime, $zone);
        if ($parsed === null || $parsed->format('Y-m-d H:i:s') !== $wallTime) {
            return null;
        }

        return $parsed->utc();
    }

    private function covers(PunchIdentity $identity, Carbon $instant): bool
    {
        return ! $identity->effective_from->greaterThan($instant)
            && ($identity->effective_to === null || $identity->effective_to->greaterThan($instant));
    }

    private function sameTimeEventExists(string $companyId, string $code, Carbon $instant): bool
    {
        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->where('event_at', $instant->copy()->utc())
            ->whereHas('identity', fn ($query) => $query->where('identity_value', $code))
            ->exists();
    }
}
