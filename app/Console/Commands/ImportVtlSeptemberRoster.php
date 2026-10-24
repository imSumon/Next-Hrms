<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class ImportVtlSeptemberRoster extends Command
{
    private const TARGET_DATABASE = 'proedge_new_hrms_test';

    private const COMPANY_ID = '01M4KG2384JEKYAE5SXC927QDF';

    private const SOURCE_SYSTEM = 'legacy_vtlhrm';

    private const CATALOG_KEY = '20';

    protected $signature = 'hrms:import-vtl-september-roster';

    protected $description = 'Import the 90 extracted VTL September roster rows into the local test database.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $rows = require database_path('data/vtl_september_2026_roster.php');
        if (! is_array($rows) || count($rows) !== 90) {
            $this->error('The September roster extract does not contain 90 rows.');

            return self::FAILURE;
        }

        $pdo = $this->connection();
        $pdo->beginTransaction();
        try {
            $this->assertTarget($pdo);
            $versionsBefore = (int) $pdo->query("SELECT count(*) FROM attendance_day_result_versions WHERE company_id = '".self::COMPANY_ID."'")->fetchColumn();
            $timezone = $this->policyTimezone($pdo);
            $counts = $this->importRows($pdo, $rows, $timezone);
            $versionsAfter = (int) $pdo->query("SELECT count(*) FROM attendance_day_result_versions WHERE company_id = '".self::COMPANY_ID."'")->fetchColumn();
            if ($versionsAfter !== $versionsBefore) {
                throw new RuntimeException('The roster import changed attendance result versions.');
            }
            $this->assertReconciled($pdo);
            $pdo->commit();
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('inserted_source_days='.$counts['source_days']);
        $this->info('inserted_roster_candidates='.$counts['candidates']);
        $this->info('VTL September roster stored in '.self::TARGET_DATABASE.'. Existing result versions were not recalculated.');

        return self::SUCCESS;
    }

    private function connection(): PDO
    {
        $pdo = new PDO(
            'pgsql:host=127.0.0.1;port=5432;dbname='.self::TARGET_DATABASE,
            (string) config('database.connections.pgsql.username'),
            (string) config('database.connections.pgsql.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->exec("SET TIME ZONE 'UTC'");

        return $pdo;
    }

    private function assertTarget(PDO $pdo): void
    {
        $meta = $pdo->query('SELECT current_database() AS db, inet_server_addr()::text AS addr, inet_server_port() AS port')->fetch(PDO::FETCH_ASSOC);
        if (! is_array($meta) || $meta['db'] !== self::TARGET_DATABASE || ! str_starts_with((string) $meta['addr'], '127.0.0.1') || (int) $meta['port'] !== 5432) {
            throw new RuntimeException('Refusing to write because the connection is not local '.self::TARGET_DATABASE.'.');
        }
    }

    private function policyTimezone(PDO $pdo): string
    {
        $statement = $pdo->prepare('SELECT timezone, punch_selection, correction_fields FROM attendance_policies WHERE company_id = :company');
        $statement->execute(['company' => self::COMPANY_ID]);
        $policy = $statement->fetch(PDO::FETCH_ASSOC);
        if (! is_array($policy) || ! in_array($policy['punch_selection'], ['first_and_last', 'margin_first_and_last'], true) || $policy['correction_fields'] !== '') {
            throw new RuntimeException('The VTL attendance policy was not left unchanged.');
        }
        $timezone = (string) $policy['timezone'];
        try {
            new \DateTimeZone($timezone);
        } catch (\Exception) {
            throw new RuntimeException('The VTL attendance policy timezone is not recognized.');
        }

        return $timezone;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{source_days: int, candidates: int}
     */
    private function importRows(PDO $pdo, array $rows, string $timezone): array
    {
        $findEmployee = $pdo->prepare('SELECT id FROM employees WHERE company_id = :company AND source_system = :source_system AND employee_code = :employee_code');
        $findShift = $pdo->prepare('SELECT id FROM shifts WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        $findSource = $pdo->prepare('SELECT id FROM roster_source_days WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        $insertSource = $pdo->prepare('INSERT INTO roster_source_days (id, company_id, employee_id, shift_id, work_date, source_day_code, always_present, source_roster_flag, window_starts_at, window_ends_at, shift_starts_at, shift_ends_at, absent_after_at, source_system, source_key, source_catalog_key, source_employee_key, source_shift_key, created_at, updated_at) VALUES (:id, :company, :employee, :shift, :work_date, :source_day_code, :always_present, :source_roster_flag, :window_starts_at, :window_ends_at, :shift_starts_at, :shift_ends_at, :absent_after_at, :source_system, :source_key, :source_catalog_key, :source_employee_key, :source_shift_key, now(), now())');
        $findCandidate = $pdo->prepare("SELECT id, shift_id, source_system FROM shift_assignment_candidates WHERE company_id = :company AND employee_id = :employee AND candidate_kind = 'roster' AND roster_work_date = :work_date");
        $insertCandidate = $pdo->prepare("INSERT INTO shift_assignment_candidates (id, company_id, employee_id, shift_id, candidate_kind, roster_work_date, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :employee, :shift, 'roster', :work_date, :source_system, :source_key, now(), now())");
        $insertedSources = 0;
        $insertedCandidates = 0;
        foreach ($rows as $row) {
            $code = (string) $row['employee_code'];
            $workDate = (string) $row['date'];
            $dayCode = (string) $row['day_status'];
            if (! preg_match('/^2026-09-\d{2}$/', $workDate) || ! in_array($dayCode, ['W', 'H', 'O'], true)) {
                throw new RuntimeException('A September roster row is outside the extracted shape.');
            }
            $findEmployee->execute([
                'company' => self::COMPANY_ID,
                'source_system' => self::SOURCE_SYSTEM,
                'employee_code' => $code,
            ]);
            $employeeId = $findEmployee->fetchColumn();
            $findShift->execute([
                'company' => self::COMPANY_ID,
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => (string) $row['shift_id'],
            ]);
            $shiftId = $findShift->fetchColumn();
            if ($employeeId === false || $shiftId === false) {
                throw new RuntimeException('Roster row '.$row['id'].' does not match an imported employee and shift.');
            }
            $sourceKey = (string) $row['id'];
            $findSource->execute([
                'company' => self::COMPANY_ID,
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => $sourceKey,
            ]);
            if ($findSource->fetchColumn() === false) {
                $insertSource->execute([
                    'id' => $this->ulid(),
                    'company' => self::COMPANY_ID,
                    'employee' => $employeeId,
                    'shift' => $shiftId,
                    'work_date' => $workDate,
                    'source_day_code' => $dayCode,
                    'always_present' => $row['always_present'] === 'Yes' ? 'true' : 'false',
                    'source_roster_flag' => (string) $row['roster'],
                    'window_starts_at' => $this->instant((string) $row['intime_margin'], $timezone),
                    'window_ends_at' => $this->instant((string) $row['out_time_margin'], $timezone),
                    'shift_starts_at' => $this->instant((string) $row['start_time'], $timezone),
                    'shift_ends_at' => $this->instant((string) $row['end_time'], $timezone),
                    'absent_after_at' => $this->instant((string) $row['absent_after'], $timezone),
                    'source_system' => self::SOURCE_SYSTEM,
                    'source_key' => $sourceKey,
                    'source_catalog_key' => self::CATALOG_KEY,
                    'source_employee_key' => $code,
                    'source_shift_key' => (string) $row['shift_id'],
                ]);
                $insertedSources++;
            }
            $findCandidate->execute([
                'company' => self::COMPANY_ID,
                'employee' => $employeeId,
                'work_date' => $workDate,
            ]);
            $candidate = $findCandidate->fetch(PDO::FETCH_ASSOC);
            if (is_array($candidate)) {
                if ($candidate['shift_id'] !== $shiftId) {
                    throw new RuntimeException('An existing roster candidate for '.$code.' on '.$workDate.' points at a different shift.');
                }

                continue;
            }
            $insertCandidate->execute([
                'id' => $this->ulid(),
                'company' => self::COMPANY_ID,
                'employee' => $employeeId,
                'shift' => $shiftId,
                'work_date' => $workDate,
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => $sourceKey,
            ]);
            $insertedCandidates++;
        }

        return ['source_days' => $insertedSources, 'candidates' => $insertedCandidates];
    }

    private function assertReconciled(PDO $pdo): void
    {
        $sourceDays = (int) $pdo->query("SELECT count(*) FROM roster_source_days WHERE company_id = '".self::COMPANY_ID."' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        $legacyCandidates = (int) $pdo->query("SELECT count(*) FROM shift_assignment_candidates WHERE company_id = '".self::COMPANY_ID."' AND candidate_kind = 'roster' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        $syntheticCandidates = (int) $pdo->query("SELECT count(*) FROM shift_assignment_candidates WHERE company_id = '".self::COMPANY_ID."' AND candidate_kind = 'roster' AND source_system = 'synthetic_attendance_test'")->fetchColumn();
        if ($sourceDays !== 90 || $legacyCandidates !== 87 || $syntheticCandidates !== 4) {
            throw new RuntimeException('The September roster reconciliation failed.');
        }
    }

    private function instant(string $wallTime, string $timezone): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $wallTime, new \DateTimeZone($timezone));
        if ($parsed === false) {
            throw new RuntimeException('A roster clock could not be read.');
        }

        return $parsed->format('Y-m-d H:i:sP');
    }

    private function ulid(): string
    {
        return strtoupper((string) Str::ulid());
    }
}
