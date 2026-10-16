<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeRegistry;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeRegistry $registry) {}

    public function index(Request $request, CompanyContext $context): View
    {
        $company = $this->company($context);
        $search = $request->string('q')->toString();

        return view('employees.index', [
            'company' => $company,
            'search' => $search,
            'employees' => $this->registry->paginate($company->id, $search),
        ]);
    }

    public function jsonIndex(Request $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $employees = $this->registry->paginate($company->id, $request->string('q')->toString());
        $page = $employees->through(fn (Employee $employee): array => [
            'id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'display_name' => $employee->display_name,
        ])->toArray();
        $page['company_name'] = $company->company_name;

        return response()->json($page);
    }

    public function jsonShow(CompanyContext $context, string $employee): JsonResponse
    {
        return response()->json($this->profilePayload($context, $this->employee($context, $employee)));
    }

    public function jsonUpdate(UpdateEmployeeRequest $request, CompanyContext $context, string $employee): JsonResponse
    {
        $record = $this->employee($context, $employee);
        $this->registry->updateName($record, $request->string('display_name')->toString());

        return response()->json($this->profilePayload($context, $record));
    }

    public function create(CompanyContext $context): View
    {
        return view('employees.create', [
            'company' => $this->company($context),
        ]);
    }

    public function store(StoreEmployeeRequest $request, CompanyContext $context): RedirectResponse
    {
        $company = $this->company($context);
        $employee = $this->registry->create(
            $company->id,
            $request->employeeAttributes(),
            $request->episodeAttributes(),
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('status', 'Employee saved.');
    }

    public function show(CompanyContext $context, string $employee): View
    {
        $record = $this->employee($context, $employee);
        $record->load(['episodes' => fn ($query) => $query->orderBy('joining_date')->orderBy('id')]);

        return view('employees.show', [
            'company' => $record->company,
            'employee' => $record,
            'auditEvents' => AuditEvent::forEmployee($record),
        ]);
    }

    public function edit(CompanyContext $context, string $employee): View
    {
        $record = $this->employee($context, $employee);

        return view('employees.edit', [
            'company' => $record->company,
            'employee' => $record,
        ]);
    }

    public function update(UpdateEmployeeRequest $request, CompanyContext $context, string $employee): RedirectResponse
    {
        $record = $this->employee($context, $employee);
        $this->registry->updateName($record, $request->string('display_name')->toString());

        return redirect()
            ->route('employees.show', $record)
            ->with('status', 'Employee updated.');
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function employee(CompanyContext $context, string $employeeId): Employee
    {
        $employee = $this->registry->find($context->companyId, $employeeId);
        abort_if($employee === null, 404);

        return $employee;
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(CompanyContext $context, Employee $record): array
    {
        $company = $this->company($context);
        $record->load(['episodes' => fn ($query) => $query
            ->where('company_id', $company->id)
            ->orderBy('joining_date')
            ->orderBy('id')]);

        return [
            'id' => $record->id,
            'employee_code' => $record->employee_code,
            'display_name' => $record->display_name,
            'company_name' => $company->company_name,
            'can_manage_employees' => $this->canManageEmployees($context),
            'display_name_changes' => $this->displayNameChanges($company->id, $record->id),
            'episodes' => $record->episodes->map(fn ($episode): array => [
                'id' => $episode->id,
                'joining_date' => $this->calendarDate($episode->joining_date),
                'last_working_date' => $this->calendarDate($episode->last_working_date),
                'episode_status' => $episode->episode_status,
            ])->values(),
        ];
    }

    private function canManageEmployees(CompanyContext $context): bool
    {
        $user = request()->user();

        return $user !== null && CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $context->companyId)
            ->where('status', 'active')
            ->where('can_manage_employees', true)
            ->exists();
    }

    /**
     * @return list<array{previous_display_name: ?string, new_display_name: ?string, actor_name: ?string, acted_at: ?string}>
     */
    private function displayNameChanges(string $companyId, string $employeeId): array
    {
        return AuditEvent::displayNameChangesFor($companyId, $employeeId)
            ->map(fn (AuditEvent $event): array => [
                'previous_display_name' => $this->storedDisplayName($event->previous_value),
                'new_display_name' => $this->storedDisplayName($event->new_value),
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
    private function storedDisplayName(?array $values): ?string
    {
        $name = $values['display_name'] ?? null;

        return is_string($name) ? $name : null;
    }

    private function calendarDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $text) !== 1) {
            abort(500, 'An employment episode date could not be read.');
        }

        return substr($text, 0, 10);
    }
}
