<?php

namespace App\Attendance;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Read-only reader for the legacy shared device log (`all_att_log`). The table
 * has no company column and stores a time with no zone; it is never written.
 */
class DeviceLogSource
{
    public function isConfigured(): bool
    {
        $connection = config('attendance.device_log.connection');

        return is_string($connection) && $connection !== '' && is_array(config('database.connections.'.$connection));
    }

    /**
     * @return array{configured: bool, connection: ?string, table: string, source_name: string, driver: ?string}
     */
    public function describe(): array
    {
        $connection = config('attendance.device_log.connection');

        return [
            'configured' => $this->isConfigured(),
            'connection' => is_string($connection) && $connection !== '' ? $connection : null,
            'table' => (string) config('attendance.device_log.table'),
            'source_name' => (string) config('attendance.device_log.source_name'),
            'driver' => $this->isConfigured() ? (string) config('database.connections.'.$connection.'.driver') : null,
        ];
    }

    /**
     * Rows whose wall-clock time falls inside the inclusive range, oldest first.
     * Returns at most $limit rows plus a flag when more exist.
     *
     * @return array{rows: list<array{id: string, date: ?string, emp_code: ?string, area: ?string, balid: ?string}>, truncated: bool}
     */
    public function read(string $fromWallTime, string $toWallTime, int $limit): array
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'source' => 'The device log source is not configured.',
            ]);
        }
        $connection = $this->readOnlyConnection();
        $rows = $connection->table((string) config('attendance.device_log.table'))
            ->select(['id', 'date', 'emp_code', 'area', 'balid'])
            ->where('date', '>=', $fromWallTime)
            ->where('date', '<=', $toWallTime)
            ->orderBy('date')
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        return [
            'rows' => $rows->take($limit)->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'date' => $row->date === null ? null : (string) $row->date,
                'emp_code' => $row->emp_code === null ? null : (string) $row->emp_code,
                'area' => $row->area === null ? null : (string) $row->area,
                'balid' => $row->balid === null ? null : (string) $row->balid,
            ])->values()->all(),
            'truncated' => $rows->count() > $limit,
        ];
    }

    private function readOnlyConnection(): ConnectionInterface
    {
        $connection = DB::connection((string) config('attendance.device_log.connection'));
        match ($connection->getDriverName()) {
            'sqlite' => $connection->statement('PRAGMA query_only = ON'),
            'mysql', 'mariadb' => $connection->statement('SET SESSION TRANSACTION READ ONLY'),
            'pgsql' => $connection->statement('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY'),
            default => throw ValidationException::withMessages([
                'source' => 'The device log source driver cannot be opened read only.',
            ]),
        };

        return $connection;
    }
}
