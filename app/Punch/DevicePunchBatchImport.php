<?php

namespace App\Punch;

use App\Attendance\DeviceLogPreview;
use Illuminate\Validation\ValidationException;

/**
 * Company-scoped device-log batch: preview, classify, and import only through
 * DeviceEventIngestion. Source wall times are passed unchanged. Conversion to
 * an offset-aware instant is Decision A and is not applied here. Exception
 * rows stay in the response; no exception table is written.
 */
class DevicePunchBatchImport
{
    /**
     * @var list<string>
     */
    public const BLOCKING_DECISIONS = [
        'Decision A: all_att_log.date stores no timezone. The batch passes that value unchanged and does not treat it as Asia/Dhaka or UTC. DeviceEventIngestion accepts only an explicit UTC offset, so naive source times cannot be recorded until the source clock is approved.',
        'Durable batch and exception tables are not approved. This response is the reconciliation record. Nothing is discarded.',
        'Authorize a live, read-only connection to the legacy master database. Only a 2026-10-02 dump and local synthetic fixtures have been inspected.',
        'Name who registers card numbers under Card identities for each company, and whether device import uses a dedicated grant. Today the existing punch-recording writer grant is required.',
    ];

    public function __construct(private DeviceLogPreview $preview, private DeviceEventIngestion $ingestion) {}

    /**
     * @return array<string, mixed>
     */
    public function run(string $companyId, string $from, string $to, bool $confirm, ?string $fingerprint): array
    {
        $preview = $this->preview->build($companyId, $from, $to);
        $expected = $this->fingerprint($preview);
        $report = [
            'source' => $preview['source'],
            'from' => $preview['from'],
            'to' => $preview['to'],
            'time_basis' => 'Source wall time is preserved as stored. It is not converted, and server receipt time is not substituted.',
            'row_count' => $preview['row_count'],
            'truncated' => $preview['truncated'],
            'max_rows' => $preview['max_rows'],
            'counts' => $preview['counts'],
            'rows' => $preview['rows'],
            'fingerprint' => $expected,
            'import_confirmed' => false,
            'writes_enabled' => false,
            'durable_exception_storage' => false,
            'status' => 'not_confirmed',
            'blocking_decisions' => self::BLOCKING_DECISIONS,
        ];
        if (! $confirm) {
            return $report;
        }
        if (! is_string($fingerprint) || ! hash_equals($expected, $fingerprint)) {
            throw ValidationException::withMessages([
                'fingerprint' => 'The source rows or card mappings changed after the preview. Preview again and confirm the new fingerprint.',
            ]);
        }

        $rows = [];
        $counts = array_fill_keys([
            DeviceEventIngestion::RECORDED,
            DeviceLogPreview::ALREADY_RECORDED,
            DeviceEventIngestion::SOURCE_EVENT_CONFLICT,
            DeviceLogPreview::POSSIBLE_DUPLICATE,
            DeviceLogPreview::UNMAPPED,
            DeviceLogPreview::AMBIGUOUS,
            DeviceLogPreview::INVALID_TIMESTAMP,
            DeviceLogPreview::INVALID_IDENTITY,
            DeviceLogPreview::INVALID_SOURCE_EVENT,
        ], 0);
        foreach ($preview['rows'] as $row) {
            $line = $this->processRow($row);
            $counts[$line['outcome']]++;
            $rows[] = $line;
        }

        $recorded = $counts[DeviceEventIngestion::RECORDED];
        $held = $preview['row_count'] - $recorded;

        return [
            'source' => $preview['source'],
            'from' => $preview['from'],
            'to' => $preview['to'],
            'time_basis' => $report['time_basis'],
            'row_count' => count($rows),
            'truncated' => $preview['truncated'],
            'max_rows' => $preview['max_rows'],
            'counts' => $counts,
            'rows' => $rows,
            'fingerprint' => $expected,
            'import_confirmed' => true,
            'writes_enabled' => false,
            'durable_exception_storage' => false,
            'status' => $held > 0 ? 'completed_with_exceptions' : 'completed',
            'blocking_decisions' => self::BLOCKING_DECISIONS,
        ];
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    public function fingerprint(array $preview): string
    {
        $payload = [
            'from' => $preview['from'] ?? null,
            'to' => $preview['to'] ?? null,
            'rows' => collect($preview['rows'] ?? [])->map(fn (array $row): array => [
                'source_event_id' => $row['source_event_id'] ?? null,
                'source_wall_time' => $row['source_wall_time'] ?? null,
                'identity_value' => $row['identity_value'] ?? null,
                'employee_id' => $row['employee_id'] ?? null,
                'outcome' => $row['outcome'] ?? null,
            ])->values()->all(),
        ];

        return hash('sha256', (string) json_encode($payload));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function processRow(array $row): array
    {
        if (($row['outcome'] ?? null) !== DeviceLogPreview::IMPORTABLE) {
            return $row + ['event_id' => $row['event_id'] ?? null];
        }

        $ingested = $this->ingestion->ingest(
            isset($row['source_event_id']) ? (string) $row['source_event_id'] : null,
            isset($row['identity_value']) ? (string) $row['identity_value'] : null,
            isset($row['source_wall_time']) ? (string) $row['source_wall_time'] : null,
        );

        return array_merge($row, [
            'outcome' => $ingested['outcome'],
            'message' => $ingested['message'],
            'event_id' => $ingested['event_id'],
            'punch_identity_id' => $ingested['punch_identity_id'],
            'employee_id' => $ingested['employee_id'] ?? $row['employee_id'] ?? null,
        ]);
    }
}
