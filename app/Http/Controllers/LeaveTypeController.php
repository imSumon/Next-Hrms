<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Leave\LeaveTypeRegistry;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\LeaveType;
use App\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
    public function __construct(private LeaveTypeRegistry $types) {}

    public function jsonIndex(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);

        return response()->json([
            'company_name' => $company->company_name,
            'data' => $this->types->list($company->id)->map(fn (LeaveType $type): array => $this->row($type))->values()->all(),
        ]);
    }

    public function jsonShow(CompanyContext $context, string $leaveType): JsonResponse
    {
        $company = $this->company($context);

        return response()->json($this->record($company, $this->find($company->id, $leaveType)));
    }

    public function jsonStore(StoreLeaveTypeRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $type = $this->types->create(
            $company->id,
            $request->validated('name'),
            $request->validated('status'),
        );

        return response()->json($this->record($company, $type), 201);
    }

    public function jsonUpdate(UpdateLeaveTypeRequest $request, CompanyContext $context, string $leaveType): JsonResponse
    {
        $company = $this->company($context);
        $updated = $this->types->update(
            $this->find($company->id, $leaveType),
            $request->validated('name'),
            $request->validated('status'),
        );

        return response()->json($this->record($company, $updated));
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function find(string $companyId, string $typeId): LeaveType
    {
        $type = $this->types->find($companyId, $typeId);
        abort_if($type === null, 404);

        return $type;
    }

    /**
     * @return array{id: string, name: string, status: string}
     */
    private function row(LeaveType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'status' => $type->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function record(Company $company, LeaveType $type): array
    {
        return [
            ...$this->row($type),
            'company_name' => $company->company_name,
            'changes' => AuditEvent::masterChangesFor($company->id, AuditEvent::SUBJECT_LEAVE_TYPE, $type->id)
                ->map(fn (AuditEvent $event): array => [
                    'previous_name' => is_string($event->previous_value['name'] ?? null) ? $event->previous_value['name'] : null,
                    'new_name' => is_string($event->new_value['name'] ?? null) ? $event->new_value['name'] : null,
                    'previous_status' => is_string($event->previous_value['status'] ?? null) ? $event->previous_value['status'] : null,
                    'new_status' => is_string($event->new_value['status'] ?? null) ? $event->new_value['status'] : null,
                    'actor_name' => is_string($event->actingUser?->name) ? $event->actingUser->name : null,
                    'acted_at' => $event->acted_at instanceof \DateTimeInterface
                        ? $event->acted_at->format(\DateTimeInterface::ATOM)
                        : null,
                ])
                ->values()
                ->all(),
        ];
    }
}
