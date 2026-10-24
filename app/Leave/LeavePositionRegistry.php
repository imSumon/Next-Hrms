<?php

namespace App\Leave;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\LeavePosition;
use App\Models\LeavePositionMovement;
use App\Models\LeaveType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeavePositionRegistry
{
    /**
     * @return Collection<int, LeavePosition>
     */
    public function list(string $companyId): Collection
    {
        return LeavePosition::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get();
    }

    public function find(string $companyId, string $positionId): ?LeavePosition
    {
        return LeavePosition::query()
            ->where('company_id', $companyId)
            ->where('id', $positionId)
            ->first();
    }

    /**
     * @param  array{
     *     employee_id: string,
     *     leave_type_id: string,
     *     proposed_amount: string,
     *     period_label: ?string,
     *     period_start: ?string,
     *     period_end: ?string,
     *     source_evidence: string,
     *     stated_basis: string,
     *     status: string
     * }  $attributes
     */
    public function create(string $companyId, array $attributes): LeavePosition
    {
        $this->assertEmployee($companyId, $attributes['employee_id']);
        $this->assertLeaveType($companyId, $attributes['leave_type_id']);
        $this->requireActor();

        try {
            return DB::transaction(function () use ($companyId, $attributes) {
                $position = LeavePosition::query()->create([
                    'company_id' => $companyId,
                    'employee_id' => $attributes['employee_id'],
                    'leave_type_id' => $attributes['leave_type_id'],
                    'proposed_amount' => $attributes['proposed_amount'],
                    'period_label' => $attributes['period_label'],
                    'period_start' => $attributes['period_start'],
                    'period_end' => $attributes['period_end'],
                    'source_evidence' => $attributes['source_evidence'],
                    'stated_basis' => $attributes['stated_basis'],
                    'status' => $attributes['status'],
                ]);

                app(EmployeeAuditTrail::class)->record(
                    $companyId,
                    AuditEvent::SUBJECT_LEAVE_POSITION,
                    (string) $position->id,
                    AuditEvent::ACTION_LEAVE_POSITION_CREATED,
                    null,
                    $this->snapshot($position),
                );

                return $position;
            });
        } catch (QueryException $exception) {
            $this->rethrowUnique($exception);
        }
    }

    /**
     * @param  array{
     *     proposed_amount: string,
     *     period_label: ?string,
     *     period_start: ?string,
     *     period_end: ?string,
     *     source_evidence: string,
     *     stated_basis: string,
     *     status: string
     * }  $attributes
     */
    public function update(LeavePosition $position, array $attributes): LeavePosition
    {
        $previous = $this->snapshot($position);

        return DB::transaction(function () use ($position, $attributes, $previous) {
            try {
                $position->fill([
                    'proposed_amount' => $attributes['proposed_amount'],
                    'period_label' => $attributes['period_label'],
                    'period_start' => $attributes['period_start'],
                    'period_end' => $attributes['period_end'],
                    'source_evidence' => $attributes['source_evidence'],
                    'stated_basis' => $attributes['stated_basis'],
                    'status' => $attributes['status'],
                ]);
                $position->save();
            } catch (QueryException $exception) {
                $this->rethrowUnique($exception);
            }

            $next = $this->snapshot($position);
            if ($previous !== $next) {
                app(EmployeeAuditTrail::class)->record(
                    (string) $position->company_id,
                    AuditEvent::SUBJECT_LEAVE_POSITION,
                    (string) $position->id,
                    AuditEvent::ACTION_LEAVE_POSITION_UPDATED,
                    $previous,
                    $next,
                );
            }

            return $position->refresh();
        });
    }

    /**
     * @param  array{
     *     amount: string,
     *     direction: string,
     *     related_reference: ?string,
     *     reverses_movement_id: ?string
     * }  $attributes
     */
    public function recordMovement(LeavePosition $position, array $attributes): LeavePositionMovement
    {
        $actorId = $this->requireActor();

        if ($attributes['reverses_movement_id'] !== null) {
            $original = LeavePositionMovement::query()
                ->where('company_id', $position->company_id)
                ->where('leave_position_id', $position->id)
                ->where('id', $attributes['reverses_movement_id'])
                ->first();
            if ($original === null) {
                throw ValidationException::withMessages([
                    'reverses_movement_id' => 'The reversed movement must belong to this position.',
                ]);
            }
        }

        return DB::transaction(function () use ($position, $attributes, $actorId) {
            $movement = LeavePositionMovement::query()->create([
                'company_id' => $position->company_id,
                'leave_position_id' => $position->id,
                'amount' => $attributes['amount'],
                'direction' => $attributes['direction'],
                'related_reference' => $attributes['related_reference'],
                'reverses_movement_id' => $attributes['reverses_movement_id'],
                'acting_user_id' => $actorId,
                'recorded_at' => now(),
            ]);

            app(EmployeeAuditTrail::class)->record(
                (string) $position->company_id,
                AuditEvent::SUBJECT_LEAVE_POSITION_MOVEMENT,
                (string) $movement->id,
                AuditEvent::ACTION_LEAVE_MOVEMENT_RECORDED,
                null,
                [
                    'leave_position_id' => $position->id,
                    'amount' => $attributes['amount'],
                    'direction' => $attributes['direction'],
                    'reverses_movement_id' => $attributes['reverses_movement_id'],
                ],
            );

            return $movement;
        });
    }

    /**
     * @return Collection<int, LeavePositionMovement>
     */
    public function movements(LeavePosition $position): Collection
    {
        return LeavePositionMovement::query()
            ->where('company_id', $position->company_id)
            ->where('leave_position_id', $position->id)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();
    }

    private function requireActor(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw new \RuntimeException('A leave-position record requires the acting user.');
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

    private function assertLeaveType(string $companyId, string $typeId): void
    {
        $exists = LeaveType::query()
            ->where('company_id', $companyId)
            ->where('id', $typeId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'The leave type must belong to the active company.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(LeavePosition $position): array
    {
        return [
            'proposed_amount' => (string) $position->proposed_amount,
            'period_label' => $position->period_label,
            'period_start' => $position->period_start?->toDateString(),
            'period_end' => $position->period_end?->toDateString(),
            'source_evidence' => $position->source_evidence,
            'stated_basis' => $position->stated_basis,
            'status' => $position->status,
        ];
    }

    private function rethrowUnique(QueryException $exception): never
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        if ($sqlState === '23505') {
            throw ValidationException::withMessages([
                'employee_id' => 'A proposed position already exists for this employee, leave type, and period evidence.',
            ]);
        }

        throw $exception;
    }
}
