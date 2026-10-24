<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use RuntimeException;

class ImportVtlAttendanceSample extends Command
{
    private const TARGET_DATABASE = 'proedge_new_hrms_test';

    private const COMPANY_ID = '01M4KG2384JEKYAE5SXC927QDF';

    private const COMPANY_NAME = 'Viyellatex Group';

    private const SOURCE_KEY = '20';

    private const SOURCE_SYSTEM = 'legacy_vtlhrm';

    protected $signature = 'hrms:import-vtl-attendance-sample';

    protected $description = 'Import the small VTL attendance sample into proedge_new_hrms_test only.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $pdo = $this->connection();
        $this->assertTarget($pdo);
        $pdo->beginTransaction();
        try {
            $this->assertTarget($pdo);
            $this->ensureCompany($pdo);
            $shiftIds = $this->importShifts($pdo);
            $employeeIds = $this->importEmployees($pdo, $shiftIds);
            $this->importMaster($pdo, 'departments', '5', 'ACCOUNTS & FINANCE', 'ACCOUNTS & FINANCE');
            $this->importMaster($pdo, 'designations', '7', 'EXECUTIVE', 'EXECUTIVE');
            $this->ensurePolicy($pdo);
            $this->assertReconciled($pdo, count($shiftIds), count($employeeIds));
            $pdo->commit();
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('VTL attendance sample stored in '.self::TARGET_DATABASE.'.');
        $this->info('Legacy employees 3 and legacy shifts '.count($this->shifts()).' are preserved. Later synthetic roster, punch, and employee rows are left in place.');
        $this->info('next_day 0 and 2 stay crosses_midnight false. next_day 1 is the only midnight shift. next_day 2 means the shift end stays on the work date and the out margin moves to the next date. Punch margins are not loaded.');
        $this->info('This company policy is test configuration for the audited VTL month. It is not a universal rule and not migration approval.');

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

    private function ensureCompany(PDO $pdo): void
    {
        $existing = $pdo->query("SELECT id, company_name, status, company_code, source_system, source_key FROM companies WHERE id = '".self::COMPANY_ID."' OR source_key = '".self::SOURCE_KEY."'")->fetchAll(PDO::FETCH_ASSOC);
        if (count($existing) > 1) {
            throw new RuntimeException('Catalog 20 matches more than one company row.');
        }
        if ($existing !== []) {
            $row = $existing[0];
            $compatible = $row['id'] === self::COMPANY_ID
                && $row['company_name'] === self::COMPANY_NAME
                && $row['status'] === 'Active'
                && $row['company_code'] === null
                && $row['source_system'] === null
                && $row['source_key'] === self::SOURCE_KEY;
            if (! $compatible) {
                throw new RuntimeException('An existing company conflicts with catalog 20. Nothing was overwritten.');
            }

            return;
        }
        $inserted = $pdo->exec("INSERT INTO companies (id, company_code, company_name, status, source_system, source_key, created_at, updated_at)
            SELECT '".self::COMPANY_ID."', NULL, '".self::COMPANY_NAME."', 'Active', NULL, '".self::SOURCE_KEY."', now(), now()
            WHERE current_database() = '".self::TARGET_DATABASE."'");
        if ($inserted !== 1) {
            throw new RuntimeException('The VTL test company insert did not write exactly one row.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function importShifts(PDO $pdo): array
    {
        $find = $pdo->prepare('SELECT id FROM shifts WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        $insertShift = $pdo->prepare('INSERT INTO shifts (id, company_id, name, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :name, :source_system, :source_key, now(), now())');
        $insertVersion = $pdo->prepare('INSERT INTO shift_versions (id, company_id, shift_id, start_time, end_time, crosses_midnight, effective_from, effective_to, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :shift_id, :start_time, :end_time, :crosses_midnight, :effective_from, :effective_to, :source_system, :source_key, now(), now())');
        $ids = [];
        foreach ($this->shifts() as $shift) {
            $sourceKey = (string) $shift['id'];
            $find->execute([
                'company' => self::COMPANY_ID,
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => $sourceKey,
            ]);
            $existing = $find->fetchColumn();
            if ($existing !== false) {
                $ids[$shift['id']] = (string) $existing;

                continue;
            }
            $shiftId = $this->ulid();
            $insertShift->execute([
                'id' => $shiftId,
                'company' => self::COMPANY_ID,
                'name' => $shift['name'],
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => $sourceKey,
            ]);
            $insertVersion->execute([
                'id' => $this->ulid(),
                'company' => self::COMPANY_ID,
                'shift_id' => $shiftId,
                'start_time' => $shift['start'],
                'end_time' => $shift['end'],
                'crosses_midnight' => $shift['crosses_midnight'] ? 'true' : 'false',
                'effective_from' => '2026-09-01',
                'effective_to' => '2026-09-30',
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => $sourceKey,
            ]);
            $ids[$shift['id']] = $shiftId;
        }

        return $ids;
    }

    /**
     * @param  array<int, string>  $shiftIds
     * @return list<string>
     */
    private function importEmployees(PDO $pdo, array $shiftIds): array
    {
        $find = $pdo->prepare('SELECT id, employee_code, display_name FROM employees WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        $insert = $pdo->prepare('INSERT INTO employees (id, company_id, employee_code, display_name, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :employee_code, :display_name, :source_system, :source_key, now(), now())');
        $findCandidate = $pdo->prepare("SELECT shift_id FROM shift_assignment_candidates WHERE company_id = :company AND employee_id = :employee AND candidate_kind = 'default'");
        $insertCandidate = $pdo->prepare("INSERT INTO shift_assignment_candidates (id, company_id, employee_id, shift_id, candidate_kind, roster_work_date, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :employee, :shift_id, 'default', NULL, :source_system, :source_key, now(), now())");
        $ids = [];
        foreach ($this->employees() as $employee) {
            if (! isset($shiftIds[$employee['shift_id']])) {
                throw new RuntimeException('Sample employee '.$employee['code'].' has no imported shift.');
            }
            $name = $this->displayName($employee['parts']);
            $find->execute([
                'company' => self::COMPANY_ID,
                'source_system' => self::SOURCE_SYSTEM,
                'source_key' => (string) $employee['id'],
            ]);
            $current = $find->fetch(PDO::FETCH_ASSOC);
            if (is_array($current)) {
                if ($current['employee_code'] !== $employee['code'] || $current['display_name'] !== $name) {
                    throw new RuntimeException('Existing employee '.$employee['code'].' does not match the source sample.');
                }
                $employeeId = (string) $current['id'];
            } else {
                $employeeId = $this->ulid();
                $insert->execute([
                    'id' => $employeeId,
                    'company' => self::COMPANY_ID,
                    'employee_code' => $employee['code'],
                    'display_name' => $name,
                    'source_system' => self::SOURCE_SYSTEM,
                    'source_key' => (string) $employee['id'],
                ]);
            }
            $findCandidate->execute(['company' => self::COMPANY_ID, 'employee' => $employeeId]);
            $candidateShift = $findCandidate->fetchColumn();
            if ($candidateShift === false) {
                $insertCandidate->execute([
                    'id' => $this->ulid(),
                    'company' => self::COMPANY_ID,
                    'employee' => $employeeId,
                    'shift_id' => $shiftIds[$employee['shift_id']],
                    'source_system' => self::SOURCE_SYSTEM,
                    'source_key' => $employee['id'].':shift_id',
                ]);
            } elseif ($candidateShift !== $shiftIds[$employee['shift_id']]) {
                throw new RuntimeException('Existing default shift for '.$employee['code'].' does not match the source.');
            }
            $ids[] = $employeeId;
        }

        return $ids;
    }

    private function importMaster(PDO $pdo, string $table, string $sourceKey, string $name, string $code): void
    {
        if (! in_array($table, ['departments', 'designations'], true)) {
            throw new RuntimeException('Unknown organization master.');
        }
        $existing = $pdo->query('SELECT name, code, status FROM '.$table." WHERE company_id = '".self::COMPANY_ID."' AND source_system = '".self::SOURCE_SYSTEM."' AND source_key = ".$pdo->quote($sourceKey))->fetch(PDO::FETCH_ASSOC);
        if (is_array($existing)) {
            if ($existing['name'] !== $name || $existing['code'] !== $code || $existing['status'] !== 'Active') {
                throw new RuntimeException('Existing '.$table.' source '.$sourceKey.' does not match the sample.');
            }

            return;
        }
        $insert = $pdo->prepare('INSERT INTO '.$table.' (id, company_id, code, name, status, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :code, :name, :status, :source_system, :source_key, now(), now())');
        $insert->execute([
            'id' => $this->ulid(),
            'company' => self::COMPANY_ID,
            'code' => $code,
            'name' => $name,
            'status' => 'Active',
            'source_system' => self::SOURCE_SYSTEM,
            'source_key' => $sourceKey,
        ]);
    }

    private function ensurePolicy(PDO $pdo): void
    {
        $existing = $pdo->query("SELECT timezone, shift_winner, punch_selection, grace_seconds, late_measured_from, early_threshold_seconds, present_status_code, incomplete_status_code, correction_fields FROM attendance_policies WHERE company_id = '".self::COMPANY_ID."'")->fetch(PDO::FETCH_ASSOC);
        $expected = [
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'roster',
            'punch_selection' => 'first_and_last',
            'grace_seconds' => '0',
            'late_measured_from' => 'shift_start',
            'early_threshold_seconds' => null,
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'correction_fields' => '',
        ];
        if (is_array($existing)) {
            foreach ($expected as $field => $value) {
                $stored = $existing[$field];
                if ($stored !== null) {
                    $stored = (string) $stored;
                }
                if ($stored !== $value) {
                    throw new RuntimeException('The VTL attendance policy already exists with a different value.');
                }
            }

            return;
        }
        $insert = $pdo->prepare('INSERT INTO attendance_policies (id, company_id, timezone, shift_winner, punch_selection, grace_seconds, late_measured_from, early_threshold_seconds, present_status_code, incomplete_status_code, correction_fields, created_at, updated_at) VALUES (:id, :company, :timezone, :shift_winner, :punch_selection, :grace_seconds, :late_measured_from, :early_threshold_seconds, :present_status_code, :incomplete_status_code, :correction_fields, now(), now())');
        $insert->execute([
            'id' => $this->ulid(),
            'company' => self::COMPANY_ID,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'roster',
            'punch_selection' => 'first_and_last',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'early_threshold_seconds' => null,
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'correction_fields' => '',
        ]);
    }

    private function assertReconciled(PDO $pdo, int $shifts, int $employees): void
    {
        $this->assertTarget($pdo);
        $storedEmployees = (int) $pdo->query("SELECT count(*) FROM employees WHERE company_id = '".self::COMPANY_ID."' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        $storedShifts = (int) $pdo->query("SELECT count(*) FROM shifts WHERE company_id = '".self::COMPANY_ID."' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        $storedCandidates = (int) $pdo->query("SELECT count(*) FROM shift_assignment_candidates WHERE company_id = '".self::COMPANY_ID."' AND candidate_kind = 'default' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        $episodes = (int) $pdo->query("SELECT count(*) FROM employment_episodes WHERE company_id = '".self::COMPANY_ID."'")->fetchColumn();
        $syntheticEmployees = (int) $pdo->query("SELECT count(*) FROM employees WHERE company_id = '".self::COMPANY_ID."' AND source_system = 'synthetic_attendance_test'")->fetchColumn();
        $departments = (int) $pdo->query("SELECT count(*) FROM departments WHERE company_id = '".self::COMPANY_ID."' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        $designations = (int) $pdo->query("SELECT count(*) FROM designations WHERE company_id = '".self::COMPANY_ID."' AND source_system = '".self::SOURCE_SYSTEM."'")->fetchColumn();
        if ($storedEmployees !== $employees || $storedShifts !== $shifts || $storedCandidates !== $employees || $episodes !== 0 || $syntheticEmployees > 1 || $departments !== 1 || $designations !== 1) {
            throw new RuntimeException('The VTL sample reconciliation failed.');
        }
    }

    private function ulid(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $time = (int) floor(microtime(true) * 1000);
        $timeChars = '';
        for ($index = 0; $index < 10; $index++) {
            $timeChars = $alphabet[$time & 31].$timeChars;
            $time >>= 5;
        }
        $random = '';
        $bytes = random_bytes(10);
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        for ($index = 0; $index < 16; $index++) {
            $random .= $alphabet[bindec(substr($bits, $index * 5, 5))];
        }

        return $timeChars.$random;
    }

    /**
     * @param  list<string>  $parts
     */
    private function displayName(array $parts): string
    {
        $kept = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $kept[] = $trimmed;
            }
        }
        if ($kept === []) {
            throw new RuntimeException('A sample employee has no usable name.');
        }

        return implode(' ', $kept);
    }

    /**
     * @return list<array{id: int, name: string, start: string, end: string, crosses_midnight: bool}>
     */
    private function shifts(): array
    {
        return [
            ['id' => 4, 'name' => 'Roster M', 'start' => '06:30:59', 'end' => '14:30:00', 'crosses_midnight' => false],
            ['id' => 5, 'name' => 'General (8AM-17.00 PM)', 'start' => '08:00:59', 'end' => '17:00:00', 'crosses_midnight' => false],
            ['id' => 10, 'name' => 'General 2 (9AM-6PM)', 'start' => '09:00:59', 'end' => '18:00:00', 'crosses_midnight' => false],
            ['id' => 11, 'name' => 'Roster D', 'start' => '14:30:59', 'end' => '22:30:00', 'crosses_midnight' => false],
            ['id' => 12, 'name' => 'Roster N', 'start' => '22:30:59', 'end' => '06:30:00', 'crosses_midnight' => true],
            ['id' => 13, 'name' => 'Eco Couture', 'start' => '08:00:59', 'end' => '17:00:00', 'crosses_midnight' => false],
            ['id' => 14, 'name' => 'Corporate', 'start' => '09:00:59', 'end' => '17:30:00', 'crosses_midnight' => false],
            ['id' => 15, 'name' => 'GDL (8:30-17:30)', 'start' => '08:30:59', 'end' => '17:30:00', 'crosses_midnight' => false],
            ['id' => 16, 'name' => 'VSL', 'start' => '08:00:59', 'end' => '17:00:00', 'crosses_midnight' => false],
            ['id' => 17, 'name' => 'General (9AM to 5:30PM)', 'start' => '09:00:59', 'end' => '17:30:00', 'crosses_midnight' => false],
            ['id' => 18, 'name' => 'RTE', 'start' => '09:00:59', 'end' => '17:30:00', 'crosses_midnight' => false],
            ['id' => 19, 'name' => 'General (9.00AM-18.00PM)', 'start' => '09:00:59', 'end' => '18:00:00', 'crosses_midnight' => false],
            ['id' => 20, 'name' => '8AM to 8PM', 'start' => '08:00:59', 'end' => '20:00:00', 'crosses_midnight' => false],
            ['id' => 21, 'name' => '8PM to 8AM', 'start' => '20:00:59', 'end' => '08:00:00', 'crosses_midnight' => true],
            ['id' => 22, 'name' => '9AM to 5PM', 'start' => '09:00:59', 'end' => '17:00:00', 'crosses_midnight' => false],
        ];
    }

    /**
     * @return list<array{id: int, code: string, parts: list<string>, shift_id: int}>
     */
    private function employees(): array
    {
        return [
            ['id' => 463, 'code' => '03123173', 'parts' => ['ANAYET', '', 'HOSSAIN'], 'shift_id' => 14],
            ['id' => 100450, 'code' => '03129487', 'parts' => ['MD. RAGIB', '', ''], 'shift_id' => 14],
            ['id' => 100654, 'code' => '05000356', 'parts' => ['NAZMUL HOSSAIN SABUJ', '', ''], 'shift_id' => 16],
        ];
    }
}
