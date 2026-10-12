<?php

namespace App\Employees;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeRegistry
{
    public const PER_PAGE = 15;

    public function __construct(private EmploymentEpisodeRegistry $episodes) {}

    public function paginate(string $companyId, ?string $search): LengthAwarePaginator
    {
        $term = str_replace(['%', '_'], '', trim((string) $search));

        return Employee::query()
            ->where('company_id', $companyId)
            ->when($term !== '', function ($query) use ($term) {
                $pattern = '%'.$term.'%';
                $query->where(function ($inner) use ($pattern) {
                    $inner->where('employee_code', 'ilike', $pattern)
                        ->orWhere('display_name', 'ilike', $pattern);
                });
            })
            ->orderBy('employee_code')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function find(string $companyId, string $employeeId): ?Employee
    {
        return Employee::query()
            ->where('company_id', $companyId)
            ->where('id', $employeeId)
            ->first();
    }

    /**
     * @param  array{employee_code: string, display_name: string}  $attributes
     * @param  array{joining_date: string, episode_status: string, last_working_date: ?string}|null  $episode
     */
    public function create(string $companyId, array $attributes, ?array $episode): Employee
    {
        try {
            return DB::transaction(function () use ($companyId, $attributes, $episode) {
                $code = trim($attributes['employee_code']);
                $name = trim($attributes['display_name']);

                $taken = Employee::query()
                    ->where('company_id', $companyId)
                    ->where('employee_code', $code)
                    ->lockForUpdate()
                    ->exists();

                if ($taken) {
                    throw ValidationException::withMessages([
                        'employee_code' => 'This employee code is already used in this company. Separation does not release a code for reuse.',
                    ]);
                }

                $employee = Employee::query()->create([
                    'company_id' => $companyId,
                    'employee_code' => $code,
                    'display_name' => $name,
                ]);

                if ($episode !== null) {
                    $this->episodes->create($employee, $episode);
                }

                return $employee;
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'employee_code' => 'This employee code is already used in this company. Separation does not release a code for reuse.',
                ]);
            }

            throw $exception;
        }
    }

    public function updateName(Employee $employee, string $displayName): Employee
    {
        $employee->display_name = trim($displayName);
        $employee->save();

        return $employee;
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();

        return $sqlState === '23505';
    }
}
