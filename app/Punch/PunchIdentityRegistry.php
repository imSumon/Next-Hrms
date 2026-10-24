<?php

namespace App\Punch;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchIdentityOverlap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registers card numbers as punch identities on existing employees of one
 * company. Uses the same table, lock and overlap rule as punch recording.
 */
class PunchIdentityRegistry
{
    public function register(string $companyId, string $employeeId, string $identityValue, string $effectiveFrom, ?string $effectiveTo, ?string $reference): PunchIdentity
    {
        $this->actorId();
        $employee = Employee::query()->where('company_id', $companyId)->where('id', $employeeId)->first();
        if (! $employee instanceof Employee) {
            throw ValidationException::withMessages(['employee_id' => 'The employee must belong to the active company.']);
        }
        $value = trim($identityValue);
        if ($value === '') {
            throw ValidationException::withMessages(['identity_value' => 'A card number is required.']);
        }
        $from = $this->instant($effectiveFrom, 'effective_from');
        $to = $effectiveTo === null || trim($effectiveTo) === '' ? null : $this->instant($effectiveTo, 'effective_to');
        if ($to !== null && ! $to->greaterThan($from)) {
            throw ValidationException::withMessages(['effective_to' => 'The end must be after the start.']);
        }
        $reference = $reference === null || trim($reference) === '' ? null : trim($reference);

        return DB::transaction(function () use ($companyId, $employee, $value, $from, $to, $reference): PunchIdentity {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            app(PunchRecordingRegistry::class)->lockCardAcrossCompanies($value);
            DB::select('select pg_advisory_xact_lock(hashtext(?)::bigint)', [$companyId.'|'.$value]);
            $existing = PunchIdentity::query()
                ->where('company_id', $companyId)
                ->where('identity_value', $value)
                ->lockForUpdate()
                ->get();
            foreach ($existing as $row) {
                if (! $this->overlaps($from, $to, $row->effective_from, $row->effective_to)) {
                    continue;
                }
                if ($row->employee_id === $employee->id) {
                    throw ValidationException::withMessages([
                        'identity_value' => 'This card is already registered for this employee in an overlapping period.',
                    ]);
                }
                if (! $this->overlapDocumented($companyId, $value, $from, $to, $row)) {
                    $other = Employee::query()->where('company_id', $companyId)->where('id', $row->employee_id)->value('employee_code');
                    throw ValidationException::withMessages([
                        'identity_value' => 'This card is already registered to employee '.$other.' in this company for part of that period. End that mapping first, or record a documented overlap.',
                    ]);
                }
            }
            $identity = PunchIdentity::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'identity_value' => $value,
                'effective_from' => $from->copy()->utc(),
                'effective_to' => $to?->copy()->utc(),
            ]);
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                AuditEvent::SUBJECT_PUNCH_IDENTITY,
                (string) $identity->id,
                AuditEvent::ACTION_PUNCH_IDENTITY_REGISTERED,
                null,
                [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'identity_value' => $value,
                    'effective_from' => $from->toIso8601String(),
                    'effective_to' => $to?->toIso8601String(),
                ],
                $reference,
            );

            return $identity;
        });
    }

    public function end(string $companyId, string $identityId, string $effectiveTo, string $reason): PunchIdentity
    {
        $this->actorId();
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Ending a card mapping needs a reason.']);
        }
        $to = $this->instant($effectiveTo, 'effective_to');
        $found = PunchIdentity::query()->where('company_id', $companyId)->where('id', $identityId)->first();
        if (! $found instanceof PunchIdentity) {
            throw ValidationException::withMessages(['identity_id' => 'The card mapping is not in this company.']);
        }

        return DB::transaction(function () use ($companyId, $found, $to, $reason): PunchIdentity {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            app(PunchRecordingRegistry::class)->lockCardAcrossCompanies($found->identity_value);
            DB::select('select pg_advisory_xact_lock(hashtext(?)::bigint)', [$companyId.'|'.$found->identity_value]);
            $identity = PunchIdentity::query()->where('company_id', $companyId)->where('id', $found->id)->lockForUpdate()->first();
            if (! $to->greaterThan($identity->effective_from)) {
                throw ValidationException::withMessages(['effective_to' => 'The end must be after the start of the mapping.']);
            }
            if ($identity->effective_to !== null && ! $to->lessThan($identity->effective_to)) {
                throw ValidationException::withMessages(['effective_to' => 'The mapping already ends at or before that time.']);
            }
            $after = PunchEvent::query()
                ->where('company_id', $companyId)
                ->where('punch_identity_id', $identity->id)
                ->where('event_at', '>=', $to->copy()->utc())
                ->count();
            if ($after > 0) {
                throw ValidationException::withMessages([
                    'effective_to' => $after.' recorded punches for this mapping fall at or after that time. The end cannot move before them.',
                ]);
            }
            $previous = $identity->effective_to?->toIso8601String();
            $identity->effective_to = $to->copy()->utc();
            $identity->save();
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                AuditEvent::SUBJECT_PUNCH_IDENTITY,
                (string) $identity->id,
                AuditEvent::ACTION_PUNCH_IDENTITY_ENDED,
                ['effective_to' => $previous],
                ['effective_to' => $to->toIso8601String(), 'identity_value' => $identity->identity_value, 'employee_id' => $identity->employee_id],
                $reason,
            );

            return $identity;
        });
    }

    /**
     * @return Collection<int, PunchIdentity>
     */
    public function list(string $companyId, string $search): Collection
    {
        $search = trim($search);
        $query = PunchIdentity::query()->where('company_id', $companyId)->with('employee');
        if ($search !== '') {
            $query->where(function ($inner) use ($search, $companyId): void {
                $inner->where('identity_value', 'ilike', '%'.$search.'%')
                    ->orWhereIn('employee_id', Employee::query()
                        ->where('company_id', $companyId)
                        ->where(fn ($employee) => $employee->where('employee_code', 'ilike', '%'.$search.'%')->orWhere('display_name', 'ilike', '%'.$search.'%'))
                        ->select('id'));
            });
        }

        return $query->orderBy('identity_value')->orderBy('effective_from')->limit(500)->get();
    }

    /**
     * Card values in this company held by more than one employee for overlapping
     * periods, and card values also held by another company for an overlapping
     * period. The other company is never named; only the shared periods, inside
     * this company's own mappings, are listed.
     *
     * @return array{in_company: list<array<string, mixed>>, other_company: list<array{identity_value: string, identity_ids: list<string>, shared_periods: list<array{from: string, to: ?string}>}>}
     */
    public function conflicts(string $companyId): array
    {
        $own = PunchIdentity::query()->where('company_id', $companyId)->with('employee')->get()->groupBy('identity_value');
        $inCompany = [];
        foreach ($own as $value => $rows) {
            $rows = $rows->values();
            for ($i = 0; $i < $rows->count(); $i++) {
                for ($j = $i + 1; $j < $rows->count(); $j++) {
                    $a = $rows[$i];
                    $b = $rows[$j];
                    if ($a->employee_id === $b->employee_id || ! $this->overlaps($a->effective_from, $a->effective_to, $b->effective_from, $b->effective_to)) {
                        continue;
                    }
                    $inCompany[] = [
                        'identity_value' => (string) $value,
                        'identity_ids' => [$a->id, $b->id],
                        'employee_codes' => [$a->employee?->employee_code, $b->employee?->employee_code],
                        'documented' => $this->overlapDocumented($companyId, (string) $value, $a->effective_from, $a->effective_to, $b),
                    ];
                }
            }
        }
        $elsewhere = PunchIdentity::query()
            ->where('company_id', '!=', $companyId)
            ->whereIn('identity_value', $own->keys())
            ->get(['identity_value', 'effective_from', 'effective_to'])
            ->groupBy('identity_value');
        $otherCompany = [];
        foreach ($elsewhere as $value => $foreign) {
            $ids = [];
            $periods = [];
            foreach ($own[$value] as $mine) {
                foreach ($foreign as $theirs) {
                    if (! $this->overlaps($mine->effective_from, $mine->effective_to, $theirs->effective_from, $theirs->effective_to)) {
                        continue;
                    }
                    $ids[$mine->id] = $mine->id;
                    $sharedTo = match (true) {
                        $mine->effective_to === null => $theirs->effective_to,
                        $theirs->effective_to === null => $mine->effective_to,
                        default => $mine->effective_to->min($theirs->effective_to),
                    };
                    $periods[] = [
                        'from' => $mine->effective_from->max($theirs->effective_from)->copy()->utc()->format(DATE_ATOM),
                        'to' => $sharedTo?->copy()->utc()->format(DATE_ATOM),
                    ];
                }
            }
            if ($ids !== []) {
                $otherCompany[] = ['identity_value' => (string) $value, 'identity_ids' => array_values($ids), 'shared_periods' => $periods];
            }
        }

        return ['in_company' => $inCompany, 'other_company' => $otherCompany];
    }

    public function registeredElsewhere(PunchIdentity $identity): bool
    {
        return PunchIdentity::query()
            ->where('company_id', '!=', $identity->company_id)
            ->where('identity_value', $identity->identity_value)
            ->get(['effective_from', 'effective_to'])
            ->contains(fn (PunchIdentity $other): bool => $this->overlaps($identity->effective_from, $identity->effective_to, $other->effective_from, $other->effective_to));
    }

    public function status(PunchIdentity $identity, ?Carbon $at = null): string
    {
        $at ??= now();
        if ($identity->effective_from->greaterThan($at)) {
            return 'scheduled';
        }

        return $identity->effective_to === null || $identity->effective_to->greaterThan($at) ? 'active' : 'ended';
    }

    private function overlaps(Carbon $aFrom, ?Carbon $aTo, Carbon $bFrom, ?Carbon $bTo): bool
    {
        return ($bTo === null || $aFrom->lessThan($bTo)) && ($aTo === null || $bFrom->lessThan($aTo));
    }

    /**
     * True when a recorded overlap covers the whole shared period of the two mappings.
     */
    private function overlapDocumented(string $companyId, string $value, Carbon $from, ?Carbon $to, PunchIdentity $other): bool
    {
        $start = $from->greaterThan($other->effective_from) ? $from : $other->effective_from;
        $end = match (true) {
            $to === null => $other->effective_to,
            $other->effective_to === null => $to,
            default => $to->lessThan($other->effective_to) ? $to : $other->effective_to,
        };
        if ($end === null) {
            return false;
        }

        return PunchIdentityOverlap::query()
            ->where('company_id', $companyId)
            ->where('identity_value', $value)
            ->where('effective_from', '<=', $start->copy()->utc())
            ->where('effective_to', '>=', $end->copy()->utc())
            ->exists();
    }

    private function instant(string $value, string $field): Carbon
    {
        $value = trim($value);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/', $value)) {
            throw ValidationException::withMessages([$field => 'The time needs a date, a time, and a UTC offset.']);
        }

        return Carbon::parse($value);
    }

    private function actorId(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw ValidationException::withMessages(['acting_user_id' => 'Card registration requires the acting user.']);
        }

        return $actorId;
    }
}
