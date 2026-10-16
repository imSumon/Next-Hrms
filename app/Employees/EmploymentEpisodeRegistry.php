<?php

namespace App\Employees;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\EmploymentEpisode;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmploymentEpisodeRegistry
{
    public const STATUSES = ['Open', 'inactive', 'separated'];

    public const CLOSED_EPISODE_REOPEN_MESSAGE = 'This inactive or separated episode cannot be set back to Open. Rejoining keeps the same employee and employee code, creates a new episode, and leaves this episode unchanged.';

    /**
     * @param  array{joining_date: string, episode_status: string, last_working_date: ?string}  $attributes
     */
    public function create(Employee $employee, array $attributes): EmploymentEpisode
    {
        try {
            return DB::transaction(function () use ($employee, $attributes) {
                return $this->persist(new EmploymentEpisode, $employee, $attributes);
            });
        } catch (QueryException $exception) {
            $this->reraiseConstraint($exception);
        }
    }

    /**
     * @param  array{joining_date: string, episode_status: string, last_working_date: ?string}  $attributes
     */
    public function update(EmploymentEpisode $episode, array $attributes): EmploymentEpisode
    {
        $employee = $episode->employee()->firstOrFail();

        try {
            return DB::transaction(function () use ($episode, $employee, $attributes) {
                return $this->persist($episode, $employee, $attributes);
            });
        } catch (QueryException $exception) {
            $this->reraiseConstraint($exception);
        }
    }

    /**
     * @param  array{joining_date: string, episode_status: string, last_working_date: ?string}  $attributes
     */
    private function persist(EmploymentEpisode $episode, Employee $employee, array $attributes): EmploymentEpisode
    {
        $status = $attributes['episode_status'];
        $lastWorkingDate = $attributes['last_working_date'] ?: null;

        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'episode_status' => 'Episode status must be Open, inactive, or separated.',
            ]);
        }

        if ($episode->exists && $episode->episode_status !== 'Open' && $status === 'Open') {
            throw ValidationException::withMessages([
                'episode_status' => self::CLOSED_EPISODE_REOPEN_MESSAGE,
            ]);
        }

        if ($status === 'Open' && $lastWorkingDate !== null) {
            throw ValidationException::withMessages([
                'last_working_date' => 'An Open episode cannot have a last working date.',
            ]);
        }

        if ($status === 'Open') {
            $otherOpen = EmploymentEpisode::query()
                ->where('employee_id', $employee->id)
                ->where('episode_status', 'Open')
                ->when($episode->exists, fn ($query) => $query->where('id', '!=', $episode->id))
                ->lockForUpdate()
                ->exists();

            if ($otherOpen) {
                throw ValidationException::withMessages([
                    'episode_status' => 'This employee already has an Open episode. Automatic separation or rejoining is not available.',
                ]);
            }
        }

        $previous = $episode->exists ? $this->auditedEpisodeValues($episode) : null;
        $newValues = [
            'joining_date' => $this->dateText($attributes['joining_date']),
            'last_working_date' => $this->dateText($lastWorkingDate),
            'episode_status' => $status,
        ];

        $episode->fill([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'joining_date' => $newValues['joining_date'],
            'last_working_date' => $newValues['last_working_date'],
            'episode_status' => $status,
        ]);
        $episode->save();

        app(EmployeeAuditTrail::class)->record(
            $employee->company_id,
            AuditEvent::SUBJECT_EPISODE,
            $episode->id,
            $previous === null ? AuditEvent::ACTION_EPISODE_CREATED : AuditEvent::ACTION_EPISODE_UPDATED,
            $previous,
            $newValues,
        );

        return $episode;
    }

    /**
     * @return array{joining_date: ?string, last_working_date: ?string, episode_status: string}
     */
    private function auditedEpisodeValues(EmploymentEpisode $episode): array
    {
        return [
            'joining_date' => $this->dateText($episode->joining_date),
            'last_working_date' => $this->dateText($episode->last_working_date),
            'episode_status' => $episode->episode_status,
        ];
    }

    private function dateText(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = (string) $value;

        return strlen($text) >= 10 ? substr($text, 0, 10) : $text;
    }

    private function reraiseConstraint(QueryException $exception): never
    {
        $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
        $detail = $exception->getMessage();

        if ($sqlState === '23505' && str_contains($detail, 'employment_episodes_one_open_per_employee')) {
            throw ValidationException::withMessages([
                'episode_status' => 'This employee already has an Open episode. Automatic separation or rejoining is not available.',
            ]);
        }

        if ($sqlState === '23514' && str_contains($detail, 'employment_episodes_status_chk')) {
            throw ValidationException::withMessages([
                'episode_status' => 'Episode status must be Open, inactive, or separated.',
            ]);
        }

        if ($sqlState === '23514' && str_contains($detail, 'employment_episodes_open_last_working_date_chk')) {
            throw ValidationException::withMessages([
                'last_working_date' => 'An Open episode cannot have a last working date.',
            ]);
        }

        throw $exception;
    }
}
