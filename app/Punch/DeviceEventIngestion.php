<?php

namespace App\Punch;

use App\Models\PunchEvent;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Identity-bound boundary for device-log punches. The company is the trusted
 * request context, the actor must be an active member holding the company's
 * punch-recording writer grant, and the source name is configuration. A row is
 * stored only when exactly one registered mapping of that company covers the
 * event time and no other company holds the card then. It never creates,
 * moves, ends, or reassigns an identity, and it never picks between holders.
 *
 * Repeats, changed repeats, and same-time events are refused for review, not
 * stored and not discarded. Card values are compared exactly; no zero-stripping
 * or other canonical form is approved. DevicePunchBatchImport may call ingest()
 * for confirmed rows. Live source import stays unauthorized.
 */
class DeviceEventIngestion
{
    public const RECORDED = 'recorded';

    public const INVALID_SOURCE_EVENT = 'invalid_source_event';

    public const INVALID_IDENTITY = 'invalid_identity';

    public const INVALID_TIMESTAMP = 'invalid_timestamp';

    public const UNMAPPED = 'unmapped';

    public const AMBIGUOUS = 'ambiguous';

    public const ALREADY_RECORDED = 'already_recorded';

    public const SOURCE_EVENT_CONFLICT = 'source_event_conflict';

    public const POSSIBLE_DUPLICATE = 'possible_duplicate';

    public function __construct(private PunchRecordingRegistry $punches) {}

    /**
     * @return array{outcome: string, message: ?string, event_id: ?string, punch_identity_id: ?string, employee_id: ?string}
     *
     * @throws AuthorizationException
     */
    public function ingest(?string $sourceEventId, ?string $identityValue, ?string $eventAt): array
    {
        [$companyId] = $this->punches->authorizedDeviceWriter();
        $sourceName = $this->punches->deviceSourceName();
        if (trim($sourceName) === '') {
            throw new \RuntimeException('No device source name is configured.');
        }

        $sourceEventId = trim((string) $sourceEventId);
        $identityValue = trim((string) $identityValue);
        if ($sourceEventId === '') {
            return $this->outcome(self::INVALID_SOURCE_EVENT, 'The source row has no source event id, so a repeat could not be recognised.');
        }
        if (! self::isCanonicalSourceEventId($sourceEventId)) {
            return $this->outcome(self::INVALID_SOURCE_EVENT, 'The source event id must be all_att_log:{id} where {id} is a positive integer with no leading zeros.');
        }
        if ($identityValue === '') {
            return $this->outcome(self::INVALID_IDENTITY, 'The source row has no card number.');
        }
        $instant = $this->instant((string) $eventAt);

        return DB::transaction(function () use ($companyId, $sourceName, $sourceEventId, $identityValue, $instant): array {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $this->punches->lockDeviceRow($companyId, $identityValue, $sourceEventId);

            $previous = $this->punches->sourceEvents($sourceName, $sourceEventId);
            if ($previous->isNotEmpty()) {
                $unchanged = $instant !== null && $previous->every(fn (PunchEvent $event): bool => $event->company_id === $companyId
                    && $event->identity?->identity_value === $identityValue
                    && (bool) $event->event_at?->equalTo($instant));

                return $unchanged
                    ? $this->outcome(self::ALREADY_RECORDED, 'This source event is already a punch event in this company with the same card and time. Repeat handling is unresolved (PUNCH-CONF-002).')
                    : $this->outcome(self::SOURCE_EVENT_CONFLICT, 'This source event id was recorded before with a different card, time, or company. Nothing was stored; the change needs review.');
            }
            if ($instant === null) {
                return $this->outcome(self::INVALID_TIMESTAMP, 'The event time needs a real date, a time, and a UTC offset.');
            }
            if ($instant->greaterThan(now())) {
                return $this->outcome(self::INVALID_TIMESTAMP, 'The event time is later than the ingestion time.');
            }

            $covering = $this->punches->coveringIdentities($companyId, $identityValue, $instant);
            if ($covering->isEmpty()) {
                return $this->outcome(self::UNMAPPED, 'No card mapping in this company covers the event time. Nothing was created; register the card first if it belongs here.');
            }
            if ($covering->count() > 1) {
                return $this->outcome(self::AMBIGUOUS, 'More than one card mapping in this company covers the event time. The import does not choose between them.');
            }
            if ($this->punches->heldByAnotherCompany($companyId, $identityValue, $instant)) {
                return $this->outcome(self::AMBIGUOUS, 'Another company also holds this card at the event time. The shared log cannot attribute the row.');
            }
            if ($this->punches->sameTimeEventExists($companyId, $identityValue, $instant)) {
                return $this->outcome(self::POSSIBLE_DUPLICATE, 'A punch for this card at the same time is already recorded. Same-time handling is unresolved (PUNCH-CONF-002).');
            }

            $event = $this->punches->appendDeviceEvent($identityValue, $instant, $sourceEventId);

            return $this->outcome(self::RECORDED, null, (string) $event->id, $event->punch_identity_id, $event->identity?->employee_id);
        });
    }

    private function instant(string $value): ?Carbon
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(Z|[+-]\d{2}:\d{2})$/', trim($value), $parts) !== 1) {
            return null;
        }
        try {
            $parsed = Carbon::parse(trim($value));
        } catch (\Throwable) {
            return null;
        }

        return $parsed->format('Y-m-d\TH:i:s') === $parts[1] ? $parsed->utc() : null;
    }

    public static function sourceEventPrefix(): string
    {
        return (string) config('attendance.device_log.source_event_prefix');
    }

    /**
     * Canonical device source-event id for a legacy row id, or null when the id
     * is not a positive decimal integer without leading zeros.
     */
    public static function sourceEventIdFromRowId(?string $id): ?string
    {
        $id = trim((string) $id);
        $prefix = self::sourceEventPrefix();
        if ($prefix === '' || preg_match('/^[1-9]\d*$/', $id) !== 1) {
            return null;
        }

        return $prefix.$id;
    }

    public static function isCanonicalSourceEventId(string $sourceEventId): bool
    {
        $prefix = self::sourceEventPrefix();

        return $prefix !== '' && preg_match('/^'.preg_quote($prefix, '/').'[1-9]\d*$/', $sourceEventId) === 1;
    }

    /**
     * @return array{outcome: string, message: ?string, event_id: ?string, punch_identity_id: ?string, employee_id: ?string}
     */
    private function outcome(string $outcome, ?string $message, ?string $eventId = null, ?string $identityId = null, ?string $employeeId = null): array
    {
        return [
            'outcome' => $outcome,
            'message' => $message,
            'event_id' => $eventId,
            'punch_identity_id' => $identityId,
            'employee_id' => $employeeId,
        ];
    }
}
