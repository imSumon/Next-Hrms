<?php

namespace App\Punch;

use App\Models\Employee;
use App\Models\PunchEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PunchLogImport
{
    public function __construct(private PunchRecordingRegistry $punches) {}

    /**
     * Validate a raw punch log and store it only when every row is accepted.
     * A preview, or any rejected or duplicate row, rolls the whole batch back.
     *
     * @param  list<array{employee_code: string, identity_value: string, event_at: string, source_event_id?: ?string}>  $rows
     * @return array{committed: bool, row_count: int, accepted_count: int, rejected_count: int, duplicate_count: int, rows: list<array<string, mixed>>}
     */
    public function run(string $companyId, string $sourceName, array $rows, bool $commit): array
    {
        $sourceName = trim($sourceName);
        if ($sourceName === '') {
            throw ValidationException::withMessages(['source_name' => 'A named source is required.']);
        }
        $report = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $report[] = $this->row($companyId, $sourceName, $index + 1, $row);
            }
            $rejected = count(array_filter($report, fn (array $line): bool => $line['outcome'] === 'rejected'));
            $duplicates = count(array_filter($report, fn (array $line): bool => $line['outcome'] === 'duplicate'));
            $committed = $commit && $rejected === 0 && $duplicates === 0 && $report !== [];
            if ($committed) {
                DB::commit();
            } else {
                DB::rollBack();
                foreach ($report as &$line) {
                    $line['event_id'] = null;
                }
                unset($line);
            }
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        return [
            'committed' => $committed,
            'row_count' => count($report),
            'accepted_count' => count($report) - $rejected - $duplicates,
            'rejected_count' => $rejected,
            'duplicate_count' => $duplicates,
            'rows' => $report,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function row(string $companyId, string $sourceName, int $line, array $row): array
    {
        $code = trim((string) ($row['employee_code'] ?? ''));
        $identity = trim((string) ($row['identity_value'] ?? ''));
        $eventAt = trim((string) ($row['event_at'] ?? ''));
        $sourceEventId = isset($row['source_event_id']) && trim((string) $row['source_event_id']) !== '' ? trim((string) $row['source_event_id']) : null;
        $outcome = ['line' => $line, 'employee_code' => $code, 'identity_value' => $identity, 'event_at' => $eventAt, 'source_event_id' => $sourceEventId, 'event_id' => null];
        try {
            $employee = $this->employee($companyId, $code);
            $instant = $this->instant($eventAt);
            if ($this->isDuplicate($companyId, $sourceName, $identity, $instant, $sourceEventId)) {
                return $outcome + ['outcome' => 'duplicate', 'message' => 'This event is already stored, or appears earlier in this file.'];
            }
            $event = DB::transaction(fn (): PunchEvent => $this->punches->record($companyId, [
                'employee_id' => $employee->id,
                'identity_value' => $identity,
                'source_name' => $sourceName,
                'event_at' => $instant->toIso8601String(),
                'source_event_id' => $sourceEventId,
            ]));
            $outcome['event_id'] = $event->id;

            return $outcome + ['outcome' => 'accepted', 'message' => null];
        } catch (ValidationException $exception) {
            return $outcome + ['outcome' => 'rejected', 'message' => collect($exception->errors())->flatten()->first()];
        }
    }

    private function employee(string $companyId, string $code): Employee
    {
        $matches = $code === '' ? collect() : Employee::query()
            ->where('company_id', $companyId)
            ->where('employee_code', $code)
            ->limit(2)
            ->get();
        if ($matches->count() !== 1) {
            throw ValidationException::withMessages([
                'employee_code' => $matches->isEmpty()
                    ? 'No employee in this company has this code.'
                    : 'More than one employee in this company has this code.',
            ]);
        }

        return $matches->first();
    }

    private function instant(string $value): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/', $value)) {
            throw ValidationException::withMessages([
                'event_at' => 'The event time needs a date, a time, and a UTC offset.',
            ]);
        }

        return Carbon::parse($value);
    }

    private function isDuplicate(string $companyId, string $sourceName, string $identity, Carbon $instant, ?string $sourceEventId): bool
    {
        if ($sourceEventId !== null) {
            $sameSourceEvent = PunchEvent::query()
                ->where('company_id', $companyId)
                ->where('source_name', $sourceName)
                ->where('source_event_id', $sourceEventId)
                ->exists();
            if ($sameSourceEvent) {
                return true;
            }
        }

        return PunchEvent::query()
            ->where('company_id', $companyId)
            ->where('event_at', $instant->copy()->utc())
            ->whereHas('identity', fn ($query) => $query->where('identity_value', $identity))
            ->exists();
    }
}
