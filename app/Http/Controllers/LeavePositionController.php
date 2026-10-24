<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeavePositionMovementRequest;
use App\Http\Requests\StoreLeavePositionRequest;
use App\Http\Requests\UpdateLeavePositionRequest;
use App\Leave\LeavePositionRegistry;
use App\Models\Company;
use App\Models\LeavePosition;
use App\Models\LeavePositionMovement;
use App\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;

class LeavePositionController extends Controller
{
    public function __construct(private LeavePositionRegistry $positions) {}

    public function jsonIndex(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);

        return response()->json([
            'company_name' => $company->company_name,
            'data' => $this->positions->list($company->id)
                ->map(fn (LeavePosition $position): array => $this->row($position))
                ->values()
                ->all(),
        ]);
    }

    public function jsonShow(CompanyContext $context, string $leavePosition): JsonResponse
    {
        $company = $this->company($context);

        return response()->json($this->record($this->find($company->id, $leavePosition)));
    }

    public function jsonStore(StoreLeavePositionRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $position = $this->positions->create($company->id, $this->positionAttributes($request->validated(), includeIdentity: true));

        return response()->json($this->record($position), 201);
    }

    public function jsonUpdate(UpdateLeavePositionRequest $request, CompanyContext $context, string $leavePosition): JsonResponse
    {
        $company = $this->company($context);
        $updated = $this->positions->update(
            $this->find($company->id, $leavePosition),
            $this->positionAttributes($request->validated(), includeIdentity: false),
        );

        return response()->json($this->record($updated));
    }

    public function jsonMovements(CompanyContext $context, string $leavePosition): JsonResponse
    {
        $company = $this->company($context);
        $position = $this->find($company->id, $leavePosition);

        return response()->json([
            'leave_position_id' => $position->id,
            'usable_as_approved_balance' => false,
            'data' => $this->positions->movements($position)
                ->map(fn (LeavePositionMovement $movement): array => $this->movementRow($movement))
                ->values()
                ->all(),
        ]);
    }

    public function jsonStoreMovement(
        StoreLeavePositionMovementRequest $request,
        CompanyContext $context,
        string $leavePosition,
    ): JsonResponse {
        $company = $this->company($context);
        $validated = $request->validated();
        $movement = $this->positions->recordMovement($this->find($company->id, $leavePosition), [
            'amount' => (string) $validated['amount'],
            'direction' => $validated['direction'],
            'related_reference' => $validated['related_reference'] ?? null,
            'reverses_movement_id' => $validated['reverses_movement_id'] ?? null,
        ]);

        return response()->json($this->movementRow($movement), 201);
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function find(string $companyId, string $positionId): LeavePosition
    {
        $position = $this->positions->find($companyId, $positionId);
        abort_if($position === null, 404);

        return $position;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function positionAttributes(array $validated, bool $includeIdentity): array
    {
        $attributes = [
            'proposed_amount' => (string) $validated['proposed_amount'],
            'period_label' => $validated['period_label'] ?? null,
            'period_start' => $validated['period_start'] ?? null,
            'period_end' => $validated['period_end'] ?? null,
            'source_evidence' => $validated['source_evidence'],
            'stated_basis' => $validated['stated_basis'],
            'status' => $validated['status'],
        ];

        if ($includeIdentity) {
            $attributes['employee_id'] = $validated['employee_id'];
            $attributes['leave_type_id'] = $validated['leave_type_id'];
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(LeavePosition $position): array
    {
        return [
            'id' => $position->id,
            'employee_id' => $position->employee_id,
            'leave_type_id' => $position->leave_type_id,
            'proposed_amount' => (string) $position->proposed_amount,
            'period_label' => $position->period_label,
            'period_start' => $position->period_start?->toDateString(),
            'period_end' => $position->period_end?->toDateString(),
            'source_evidence' => $position->source_evidence,
            'stated_basis' => $position->stated_basis,
            'status' => $position->status,
            'confirmation_recorded_at' => null,
            'confirmation_actor_id' => null,
            'confirmation_decision' => null,
            'usable_as_approved_balance' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function record(LeavePosition $position): array
    {
        return $this->row($position);
    }

    /**
     * @return array<string, mixed>
     */
    private function movementRow(LeavePositionMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'leave_position_id' => $movement->leave_position_id,
            'amount' => (string) $movement->amount,
            'direction' => $movement->direction,
            'related_reference' => $movement->related_reference,
            'reverses_movement_id' => $movement->reverses_movement_id,
            'acting_user_id' => $movement->acting_user_id,
            'recorded_at' => $movement->recorded_at instanceof \DateTimeInterface
                ? $movement->recorded_at->format(\DateTimeInterface::ATOM)
                : null,
            'usable_as_approved_balance' => false,
        ];
    }
}
