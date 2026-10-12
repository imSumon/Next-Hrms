<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeRegistry;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Tenancy\CompanyContext;
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

        return view('employees.show', [
            'company' => $record->company,
            'employee' => $record->load(['episodes' => fn ($query) => $query->orderBy('joining_date')->orderBy('id')]),
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
}
