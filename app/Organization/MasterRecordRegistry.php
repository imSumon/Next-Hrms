<?php

namespace App\Organization;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterRecordRegistry
{
    public const PER_PAGE = 15;

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        private string $modelClass,
        private string $subjectKind,
    ) {}

    public function paginate(string $companyId, ?string $search): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->modelClass::query()
            ->where('company_id', $companyId)
            ->when($term !== '', function ($query) use ($term) {
                $pattern = '%'.$this->escapeLike($term).'%';
                $query->where(function ($inner) use ($pattern) {
                    $inner->where('code', 'ilike', $pattern)
                        ->orWhere('name', 'ilike', $pattern);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function find(string $companyId, string $recordId): ?Model
    {
        return $this->modelClass::query()
            ->where('company_id', $companyId)
            ->where('id', $recordId)
            ->first();
    }

    /**
     * @param  array{code: ?string, name: string, status: string}  $attributes
     */
    public function create(string $companyId, array $attributes): Model
    {
        try {
            return DB::transaction(function () use ($companyId, $attributes) {
                $code = $this->normalizedCode($attributes['code'] ?? null);
                $this->assertCodeAvailable($companyId, $code);

                return $this->modelClass::query()->create([
                    'company_id' => $companyId,
                    'code' => $code,
                    'name' => trim($attributes['name']),
                    'status' => $attributes['status'],
                ]);
            });
        } catch (QueryException $exception) {
            $this->rethrowUnique($exception);
        }
    }

    public function update(Model $record, string $name, string $status): Model
    {
        $previousName = $record->getAttribute('name');
        $previousStatus = $record->getAttribute('status');
        $newName = trim($name);

        return DB::transaction(function () use ($record, $previousName, $previousStatus, $newName, $status) {
            $record->setAttribute('name', $newName);
            $record->setAttribute('status', $status);
            $record->save();

            if ($previousName !== $newName) {
                app(EmployeeAuditTrail::class)->record(
                    (string) $record->getAttribute('company_id'),
                    $this->subjectKind,
                    (string) $record->getAttribute('id'),
                    AuditEvent::ACTION_NAME_UPDATED,
                    ['name' => $previousName],
                    ['name' => $newName],
                );
            }

            if ($previousStatus !== $status) {
                app(EmployeeAuditTrail::class)->record(
                    (string) $record->getAttribute('company_id'),
                    $this->subjectKind,
                    (string) $record->getAttribute('id'),
                    AuditEvent::ACTION_STATUS_UPDATED,
                    ['status' => $previousStatus],
                    ['status' => $status],
                );
            }

            return $record;
        });
    }

    private function assertCodeAvailable(string $companyId, ?string $code): void
    {
        if ($code === null) {
            return;
        }

        $taken = $this->modelClass::query()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->lockForUpdate()
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'code' => 'This code is already used in this company.',
            ]);
        }
    }

    private function normalizedCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $trimmed = trim($code);

        return $trimmed === '' ? null : $trimmed;
    }

    private function rethrowUnique(QueryException $exception): never
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        if ($sqlState === '23505') {
            throw ValidationException::withMessages([
                'code' => 'This code is already used in this company.',
            ]);
        }

        throw $exception;
    }

    private function escapeLike(string $term): string
    {
        return addcslashes($term, '\\%_');
    }
}
