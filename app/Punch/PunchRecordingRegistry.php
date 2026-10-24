<?php

namespace App\Punch;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchIdentityOverlap;
use App\Tenancy\CompanyContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PunchRecordingRegistry
{
    /**
     * @return Collection<int, PunchEvent>
     */
    public function list(string $companyId): Collection
    {
        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->with('identity')
            ->orderBy('event_at')
            ->orderBy('id')
            ->get();
    }

    public function find(string $companyId, string $eventId): ?PunchEvent
    {
        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->where('id', $eventId)
            ->with('identity')
            ->first();
    }

    /**
     * @param  array{
     *     employee_id: string,
     *     identity_value: string,
     *     source_name: string,
     *     event_at: string,
     *     source_event_id?: ?string
     * }  $attributes
     */
    public function record(string $companyId, array $attributes): PunchEvent
    {
        $actorId = $this->requireActor();
        $this->assertEmployee($companyId, $attributes['employee_id']);
        $identityValue = $this->requiredText($attributes['identity_value'], 'identity_value');
        $sourceName = $this->requiredText($attributes['source_name'], 'source_name');
        $eventAt = $this->eventAt($attributes['event_at']);
        $sourceEventId = $this->optionalText($attributes['source_event_id'] ?? null, 'source_event_id');
        if ($this->looksLikeDeviceRow($sourceName, $sourceEventId)) {
            throw ValidationException::withMessages([
                'source_name' => 'Device-log rows are recorded only through the device import boundary.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $attributes, $actorId, $identityValue, $sourceName, $eventAt, $sourceEventId) {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $identity = $this->resolveIdentity($companyId, $attributes['employee_id'], $identityValue, $eventAt);

            return $this->appendEvent($companyId, $identity, $sourceName, $eventAt, $sourceEventId, $actorId);
        });
    }

    /**
     * Stores one device-log event for the trusted active company and the
     * signed-in writer. Takes the source-event, card-wide, and company locks
     * itself and re-checks every device safeguard, whatever the caller already
     * checked: exactly one mapping of this company covers the time, no other
     * company holds the card then, the source event id was never recorded, and
     * no punch for the card shares the time. Never creates, moves, ends, or
     * reassigns an identity.
     *
     * @throws AuthorizationException
     */
    public function appendDeviceEvent(string $identityValue, Carbon $eventAt, string $sourceEventId): PunchEvent
    {
        [$companyId, $actorId] = $this->authorizedDeviceWriter();

        return DB::transaction(function () use ($companyId, $identityValue, $eventAt, $sourceEventId, $actorId): PunchEvent {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $this->lockDeviceRow($companyId, $identityValue, $sourceEventId);
            $covering = $this->coveringIdentities($companyId, $identityValue, $eventAt);
            if ($covering->count() !== 1
                || $this->heldByAnotherCompany($companyId, $identityValue, $eventAt)
                || $this->sourceEvents($this->deviceSourceName(), $sourceEventId)->isNotEmpty()
                || $this->sameTimeEventExists($companyId, $identityValue, $eventAt)) {
                throw new \LogicException('A device event needs exactly one covering card mapping in one company, an unrecorded source event id, and no same-time punch for the card.');
            }

            return $this->appendEvent($companyId, $covering->first(), $this->deviceSourceName(), $eventAt, $sourceEventId, $actorId);
        });
    }

    public function deviceSourceName(): string
    {
        return (string) config('attendance.device_log.source_name');
    }

    /**
     * The trusted active company and the signed-in actor, who must be an active
     * member holding that company's punch-recording writer grant.
     *
     * @return array{0: string, 1: string}
     *
     * @throws AuthorizationException
     */
    public function authorizedDeviceWriter(): array
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '' || ! app()->bound(CompanyContext::class)) {
            throw new AuthorizationException('A device import needs a signed-in user and the active company context.');
        }
        $companyId = app(CompanyContext::class)->companyId;
        $member = CompanyMembership::query()
            ->where('user_id', $actorId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();
        if (! $member || ! app(PunchRecordingWriterGrant::class)->allows($actorId, $companyId)) {
            throw new AuthorizationException('Device import is not authorized for the active company.');
        }

        return [$companyId, $actorId];
    }

    /**
     * Source-event lock, then card-wide lock, then company card lock. Must run
     * inside a transaction; the locks are held until it ends.
     */
    public function lockDeviceRow(string $companyId, string $identityValue, string $sourceEventId): void
    {
        DB::select('select pg_advisory_xact_lock(hashtext(?)::bigint)', ['device-source|'.$this->deviceSourceName().'|'.$sourceEventId]);
        $this->lockCardAcrossCompanies($identityValue);
        $this->lockCompanyCard($companyId, $identityValue);
    }

    /**
     * Fails closed for anything that names the device log or carries its
     * source-event id prefix. Only the device import boundary records those.
     */
    private function looksLikeDeviceRow(string $sourceName, ?string $sourceEventId): bool
    {
        $normalized = fn (string $text): string => strtolower((string) preg_replace('/\s+/', ' ', trim($text)));
        $name = $normalized($sourceName);
        $markers = array_filter([
            $normalized($this->deviceSourceName()),
            $normalized((string) config('attendance.device_log.table')),
        ], fn (string $marker): bool => $marker !== '');
        foreach ($markers as $marker) {
            if (str_contains($name, $marker)) {
                return true;
            }
        }
        $prefix = $normalized((string) config('attendance.device_log.source_event_prefix'));

        return $sourceEventId !== null && $prefix !== '' && str_starts_with($normalized($sourceEventId), $prefix);
    }

    /**
     * Serialises every writer of a card value across all companies. Take it
     * before the company card lock to keep one lock order.
     */
    public function lockCardAcrossCompanies(string $identityValue): void
    {
        DB::select('select pg_advisory_xact_lock(hashtext(?)::bigint)', ['punch-card|'.$identityValue]);
    }

    /**
     * @return Collection<int, PunchIdentity>
     */
    public function coveringIdentities(string $companyId, string $identityValue, Carbon $at): Collection
    {
        return PunchIdentity::query()
            ->where('company_id', $companyId)
            ->where('identity_value', $identityValue)
            ->lockForUpdate()
            ->get()
            ->filter(fn (PunchIdentity $identity): bool => $this->covers($identity, $at))
            ->values();
    }

    public function heldByAnotherCompany(string $companyId, string $identityValue, Carbon $at): bool
    {
        return PunchIdentity::query()
            ->where('company_id', '!=', $companyId)
            ->where('identity_value', $identityValue)
            ->get()
            ->contains(fn (PunchIdentity $identity): bool => $this->covers($identity, $at));
    }

    /**
     * Earlier events for a source event id in any company.
     *
     * @return Collection<int, PunchEvent>
     */
    public function sourceEvents(string $sourceName, string $sourceEventId): Collection
    {
        return PunchEvent::query()
            ->where('source_name', $sourceName)
            ->where('source_event_id', $sourceEventId)
            ->with('identity')
            ->get();
    }

    public function sameTimeEventExists(string $companyId, string $identityValue, Carbon $at): bool
    {
        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->where('event_at', $at->copy()->utc())
            ->whereHas('identity', fn ($query) => $query->where('identity_value', $identityValue))
            ->exists();
    }

    private function lockCompanyCard(string $companyId, string $identityValue): void
    {
        DB::select('select pg_advisory_xact_lock(hashtext(?)::bigint)', [$companyId.'|'.$identityValue]);
    }

    private function appendEvent(string $companyId, PunchIdentity $identity, string $sourceName, Carbon $eventAt, ?string $sourceEventId, string $actorId): PunchEvent
    {
        if ($identity->company_id !== $companyId || ! $this->covers($identity, $eventAt)) {
            throw new \LogicException('A punch event can only be stored against an identity of the same company that covers the event time.');
        }

        $event = PunchEvent::query()->create([
            'company_id' => $companyId,
            'punch_identity_id' => $identity->id,
            'source_name' => $sourceName,
            'event_at' => $eventAt->copy()->utc(),
            'ingested_at' => now()->utc(),
            'source_event_id' => $sourceEventId,
            'acting_user_id' => $actorId,
            'withdrawal_status' => 'accepted',
        ]);
        $event->setRelation('identity', $identity);

        app(EmployeeAuditTrail::class)->record(
            $companyId,
            AuditEvent::SUBJECT_PUNCH_EVENT,
            (string) $event->id,
            AuditEvent::ACTION_PUNCH_EVENT_RECORDED,
            null,
            [
                'employee_id' => $identity->employee_id,
                'identity_value' => $identity->identity_value,
                'source_name' => $event->source_name,
                'event_at' => $event->event_at?->toIso8601String(),
                'ingested_at' => $event->ingested_at?->toIso8601String(),
                'source_event_id' => $event->source_event_id,
                'acting_user_id' => $event->acting_user_id,
            ],
        );

        return $event;
    }

    private function requireActor(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw new \RuntimeException('A punch record requires the acting user.');
        }

        return $actorId;
    }

    private function assertEmployee(string $companyId, string $employeeId): void
    {
        $exists = Employee::query()
            ->where('company_id', $companyId)
            ->where('id', $employeeId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'employee_id' => 'The employee must belong to the active company.',
            ]);
        }
    }

    private function requiredText(string $value, string $field): string
    {
        $text = trim($value);
        if ($text === '') {
            throw ValidationException::withMessages([
                $field => 'This field is required.',
            ]);
        }

        return $text;
    }

    private function optionalText(?string $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim($value);
        if ($text === '') {
            throw ValidationException::withMessages([
                $field => 'This field is required.',
            ]);
        }

        return $text;
    }

    private function eventAt(string $value): Carbon
    {
        $value = trim($value);
        if ($value === '') {
            throw ValidationException::withMessages([
                'event_at' => 'The event time is not a valid time.',
            ]);
        }

        try {
            $parsed = Carbon::parse($value);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'event_at' => 'The event time is not a valid time.',
            ]);
        }

        return $parsed;
    }

    private function resolveIdentity(string $companyId, string $employeeId, string $identityValue, Carbon $eventAt): PunchIdentity
    {
        $this->lockCardAcrossCompanies($identityValue);
        $this->lockCompanyCard($companyId, $identityValue);

        $rows = PunchIdentity::query()
            ->where('company_id', $companyId)
            ->where('identity_value', $identityValue)
            ->lockForUpdate()
            ->get();

        $covering = $rows->filter(fn (PunchIdentity $row): bool => $this->covers($row, $eventAt));
        $otherEmployees = $covering
            ->filter(fn (PunchIdentity $row): bool => $row->employee_id !== $employeeId);
        if ($otherEmployees->isNotEmpty() && ! $this->overlapPermits($companyId, $identityValue, $eventAt)) {
            throw ValidationException::withMessages([
                'identity_value' => 'This punch identity is already active for another employee in this company.',
            ]);
        }

        $mine = $covering->first(fn (PunchIdentity $row): bool => $row->employee_id === $employeeId);
        if ($mine instanceof PunchIdentity) {
            return $mine;
        }

        $openMine = $rows->first(function (PunchIdentity $row) use ($employeeId): bool {
            return $row->employee_id === $employeeId && $row->effective_to === null;
        });
        if ($openMine instanceof PunchIdentity && $openMine->effective_from->greaterThan($eventAt)) {
            $previous = $openMine->effective_from->toIso8601String();
            $openMine->effective_from = $eventAt->copy()->utc();
            $openMine->save();
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                AuditEvent::SUBJECT_PUNCH_IDENTITY,
                (string) $openMine->id,
                AuditEvent::ACTION_PUNCH_IDENTITY_EFFECTIVE_FROM_MOVED_EARLIER,
                ['effective_from' => $previous],
                ['effective_from' => $eventAt->toIso8601String(), 'employee_id' => $employeeId, 'identity_value' => $identityValue],
            );

            return $openMine;
        }

        $identity = PunchIdentity::query()->create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'identity_value' => $identityValue,
            'effective_from' => $eventAt->copy()->utc(),
            'effective_to' => null,
        ]);
        app(EmployeeAuditTrail::class)->record(
            $companyId,
            AuditEvent::SUBJECT_PUNCH_IDENTITY,
            (string) $identity->id,
            AuditEvent::ACTION_PUNCH_IDENTITY_RECORDED,
            null,
            [
                'employee_id' => $employeeId,
                'identity_value' => $identityValue,
                'effective_from' => $eventAt->toIso8601String(),
            ],
        );

        return $identity;
    }

    private function covers(PunchIdentity $row, Carbon $eventAt): bool
    {
        if ($row->effective_from->greaterThan($eventAt)) {
            return false;
        }

        return $row->effective_to === null || $row->effective_to->greaterThan($eventAt);
    }

    private function overlapPermits(string $companyId, string $identityValue, Carbon $eventAt): bool
    {
        return PunchIdentityOverlap::query()
            ->where('company_id', $companyId)
            ->where('identity_value', $identityValue)
            ->where('effective_from', '<=', $eventAt->copy()->utc())
            ->where('effective_to', '>', $eventAt->copy()->utc())
            ->exists();
    }
}
