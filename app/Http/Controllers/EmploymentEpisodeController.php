<?php

namespace App\Http\Controllers;

use App\Employees\EmployeeRegistry;
use App\Employees\EmploymentEpisodeRegistry;
use App\Http\Requests\StoreEmploymentEpisodeRequest;
use App\Http\Requests\UpdateEmploymentEpisodeRequest;
use App\Models\Employee;
use App\Models\EmploymentEpisode;
use App\Tenancy\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmploymentEpisodeController extends Controller
{
    public function __construct(private EmploymentEpisodeRegistry $episodes) {}

    public function create(CompanyContext $context, string $employee): View
    {
        $record = $this->findEmployee($context, $employee);

        return view('employees.episodes.create', [
            'company' => $record->company,
            'employee' => $record,
        ]);
    }

    public function store(StoreEmploymentEpisodeRequest $request, CompanyContext $context, string $employee): RedirectResponse
    {
        $record = $this->findEmployee($context, $employee);
        $this->episodes->create($record, $request->episodeAttributes());

        return redirect()
            ->route('employees.show', $record)
            ->with('status', 'Employment episode saved.');
    }

    public function edit(CompanyContext $context, string $employee, string $episode): View
    {
        [$record, $row] = $this->findPair($context, $employee, $episode);

        return view('employees.episodes.edit', [
            'company' => $record->company,
            'employee' => $record,
            'episode' => $row,
        ]);
    }

    public function update(UpdateEmploymentEpisodeRequest $request, CompanyContext $context, string $employee, string $episode): RedirectResponse
    {
        [$record, $row] = $this->findPair($context, $employee, $episode);
        $this->episodes->update($row, $request->episodeAttributes());

        return redirect()
            ->route('employees.show', $record)
            ->with('status', 'Employment episode updated.');
    }

    private function findEmployee(CompanyContext $context, string $employeeId): Employee
    {
        $employee = app(EmployeeRegistry::class)->find($context->companyId, $employeeId);
        abort_if($employee === null, 404);

        return $employee;
    }

    /**
     * @return array{0: Employee, 1: EmploymentEpisode}
     */
    private function findPair(CompanyContext $context, string $employeeId, string $episodeId): array
    {
        $employee = $this->findEmployee($context, $employeeId);
        $episode = EmploymentEpisode::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('id', $episodeId)
            ->first();
        abort_if($episode === null, 404);

        return [$employee, $episode];
    }
}
