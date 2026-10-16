<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationMasterRequest;
use App\Http\Requests\UpdateOrganizationMasterRequest;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Organization\DepartmentRegistry;
use App\Organization\DesignationRegistry;
use App\Organization\MasterRecordRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationMasterController extends Controller
{
    public function __construct(
        private DepartmentRegistry $departments,
        private DesignationRegistry $designations,
    ) {}

    public function jsonIndex(Request $request, CompanyContext $context, string $master): JsonResponse
    {
        $company = $this->company($context);
        $registry = $this->registry($master);
        $page = $registry->paginate($company->id, $request->string('q')->toString())
            ->through(fn (Model $record): array => $this->rowPayload($record))
            ->toArray();
        $page['company_name'] = $company->company_name;
        $page['can_manage_organization_masters'] = $this->canManage($context);

        return response()->json($page);
    }

    public function jsonShow(CompanyContext $context, string $master, string $record): JsonResponse
    {
        $company = $this->company($context);

        return response()->json($this->recordPayload($context, $company, $master, $this->find($context, $master, $record)));
    }

    public function jsonStore(StoreOrganizationMasterRequest $request, CompanyContext $context, string $master): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $record = $this->registry($master)->create($company->id, [
            'code' => $validated['code'] ?? null,
            'name' => $validated['name'],
            'status' => $validated['status'],
        ]);

        return response()->json($this->recordPayload($context, $company, $master, $record), 201);
    }

    public function jsonUpdate(UpdateOrganizationMasterRequest $request, CompanyContext $context, string $master, string $record): JsonResponse
    {
        $company = $this->company($context);
        $registry = $this->registry($master);
        $existing = $this->find($context, $master, $record);
        $updated = $registry->update($existing, $request->validated('name'), $request->validated('status'));

        return response()->json($this->recordPayload($context, $company, $master, $updated));
    }

    private function registry(string $master): MasterRecordRegistry
    {
        return match ($master) {
            'department' => $this->departments,
            'designation' => $this->designations,
            default => abort(404),
        };
    }

    private function subjectKind(string $master): string
    {
        return match ($master) {
            'department' => AuditEvent::SUBJECT_DEPARTMENT,
            'designation' => AuditEvent::SUBJECT_DESIGNATION,
            default => abort(404),
        };
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function find(CompanyContext $context, string $master, string $recordId): Model
    {
        $record = $this->registry($master)->find($context->companyId, $recordId);
        abort_if($record === null, 404);

        return $record;
    }

    private function canManage(CompanyContext $context): bool
    {
        $user = request()->user();

        return $user !== null && CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $context->companyId)
            ->where('status', 'active')
            ->where('can_manage_organization_masters', true)
            ->exists();
    }

    /**
     * @return array{id: string, code: ?string, name: string, status: string}
     */
    private function rowPayload(Model $record): array
    {
        $code = $record->getAttribute('code');

        return [
            'id' => (string) $record->getAttribute('id'),
            'code' => is_string($code) ? $code : null,
            'name' => (string) $record->getAttribute('name'),
            'status' => (string) $record->getAttribute('status'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recordPayload(CompanyContext $context, Company $company, string $master, Model $record): array
    {
        return [
            ...$this->rowPayload($record),
            'company_name' => $company->company_name,
            'can_manage_organization_masters' => $this->canManage($context),
            'changes' => $this->changes($company->id, $this->subjectKind($master), (string) $record->getAttribute('id')),
        ];
    }

    /**
     * @return list<array{previous_name: ?string, new_name: ?string, previous_status: ?string, new_status: ?string, actor_name: ?string, acted_at: ?string}>
     */
    private function changes(string $companyId, string $subjectKind, string $recordId): array
    {
        return AuditEvent::masterChangesFor($companyId, $subjectKind, $recordId)
            ->map(fn (AuditEvent $event): array => [
                'previous_name' => $this->storedText($event->previous_value, 'name'),
                'new_name' => $this->storedText($event->new_value, 'name'),
                'previous_status' => $this->storedText($event->previous_value, 'status'),
                'new_status' => $this->storedText($event->new_value, 'status'),
                'actor_name' => is_string($event->actingUser?->name) ? $event->actingUser->name : null,
                'acted_at' => $event->acted_at instanceof \DateTimeInterface
                    ? $event->acted_at->format(\DateTimeInterface::ATOM)
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $values
     */
    private function storedText(?array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
