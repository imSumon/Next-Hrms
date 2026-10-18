<?php

namespace App\Console\Commands;

use Hrms\EmployeeClassification\ClassificationException;
use Hrms\EmployeeClassification\GateSet;
use Hrms\EmployeeClassification\Runner;
use Illuminate\Console\Command;
use PDO;
use RuntimeException;

class ImportProedgeTestEmployees extends Command
{
    private const TARGET_DATABASE = 'proedge_new_hrms_test';

    private const COMPANY_ID = '01M4KG2364VXPYVZCM7HZTGPB1';

    private const COMPANY_NAME = 'Pro-edge Associates Limited';

    private const SOURCE_KEY = '24';

    protected $signature = 'hrms:import-proedge-test-employees';

    protected $description = 'Import the approved Proedge employee test set into proedge_new_hrms_test only. This is not migration approval.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        require_once base_path('tools/employee-classification/bootstrap.php');

        $pdo = $this->connection();
        $this->assertTarget($pdo);
        $this->ensureCompany($pdo);

        $output = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hrms-proedge-approved-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(3)).'.json';
        $note = 'Bounded Proedge test migration authorized by the Product Owner execution instruction on 2026-10-19. D1, D2, D3, and D6 are approved for this test load only. D4 stays proposed. Step 20R was not rewritten. This company row is not migration approval.';
        try {
            (new Runner)->classifyCatalog(dirname(base_path()), $output, GateSet::explicit([
                'd1' => 'approved',
                'd2' => 'approved',
                'd3' => 'approved',
                'd4' => 'proposed',
                'd6' => 'approved',
            ]), 24, $note);
        } catch (ClassificationException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        /** @var array<string, mixed> $report */
        $report = json_decode((string) file_get_contents($output), true, 512, JSON_THROW_ON_ERROR);
        $this->assertReport($report);
        $employees = $this->catalogRows($report['employee_classifications']);
        $episodes = $this->catalogRows($report['episode_classifications']);
        $this->assertTraceable($employees, $episodes);

        $pdo->beginTransaction();
        try {
            $this->assertTarget($pdo);
            $result = $this->import($pdo, $employees, $episodes);
            $this->assertDatabaseMatchesReport($pdo, $employees, $episodes);
            $pdo->commit();
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->report($report, $employees, $episodes, $result, $output);

        return self::SUCCESS;
    }

    private function connection(): PDO
    {
        $user = (string) config('database.connections.pgsql.username');
        $password = (string) config('database.connections.pgsql.password');
        $pdo = new PDO(
            'pgsql:host=127.0.0.1;port=5432;dbname='.self::TARGET_DATABASE,
            $user,
            $password,
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
        $this->assertTarget($pdo);
        $existing = $pdo->query("SELECT id, company_name, status, company_code, source_system, source_key FROM companies WHERE id = '".self::COMPANY_ID."' OR source_key = '".self::SOURCE_KEY."'")->fetchAll(PDO::FETCH_ASSOC);
        if (count($existing) > 1) {
            throw new RuntimeException('Catalog 24 matches more than one company row in the test database.');
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
                throw new RuntimeException('An existing test company conflicts with catalog 24. Nothing was overwritten.');
            }
            $this->info('Test company already present and compatible. It is not migration approval.');

            return;
        }

        $inserted = $pdo->exec("INSERT INTO companies (id, company_code, company_name, status, source_system, source_key, created_at, updated_at)
            SELECT '".self::COMPANY_ID."', NULL, '".self::COMPANY_NAME."', 'Active', NULL, '".self::SOURCE_KEY."', now(), now()
            WHERE current_database() = '".self::TARGET_DATABASE."'
              AND NOT EXISTS (
                SELECT 1 FROM companies WHERE id = '".self::COMPANY_ID."' OR source_key = '".self::SOURCE_KEY."'
              )");
        if ($inserted !== 1) {
            throw new RuntimeException('The test company insert did not write exactly one row.');
        }
        $this->info('Test company fixture stored in '.self::TARGET_DATABASE.'. This is not migration approval.');
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function assertReport(array $report): void
    {
        if (($report['mode'] ?? '') !== 'explicit_gates') {
            throw new RuntimeException('The classification was not run with explicit gates.');
        }
        $gates = $report['gates'] ?? [];
        foreach (['d1', 'd2', 'd3', 'd6'] as $gate) {
            if (($gates[$gate] ?? '') !== 'approved') {
                throw new RuntimeException('Gate '.$gate.' is not approved on this classification.');
            }
        }
        if (($gates['d4'] ?? '') !== 'proposed') {
            throw new RuntimeException('D4 must stay proposed.');
        }
        if (($report['target_database_write'] ?? true) !== false) {
            throw new RuntimeException('The classifier must not write the target database.');
        }
        if (($report['decision_record']['company_migration_approved'] ?? true) !== false) {
            throw new RuntimeException('The classification incorrectly records migration approval.');
        }
        $companies = $report['companies'] ?? [];
        if (! is_array($companies) || count($companies) !== 1 || (int) ($companies[0]['catalog_company_id'] ?? 0) !== 24) {
            throw new RuntimeException('The classification is not the Proedge catalog 24 extract.');
        }
        $employeeSummary = $companies[0]['employees'] ?? [];
        $episodeSummary = $companies[0]['episodes'] ?? [];
        if ((int) ($employeeSummary['unexplained'] ?? 1) !== 0 || (int) ($episodeSummary['unexplained'] ?? 1) !== 0) {
            throw new RuntimeException('The dry run has an unexplained row.');
        }
        if ((int) ($employeeSummary['source_count'] ?? 0) !== (int) ($employeeSummary['proposed_load'] ?? -1) + (int) ($employeeSummary['quarantined'] ?? -1) + (int) ($employeeSummary['approved_exceptions'] ?? -1)) {
            throw new RuntimeException('The employee dry-run equation does not balance.');
        }
        if ((int) ($episodeSummary['source_count'] ?? 0) !== (int) ($episodeSummary['proposed_load'] ?? -1) + (int) ($episodeSummary['quarantined'] ?? -1) + (int) ($episodeSummary['approved_exceptions'] ?? -1)) {
            throw new RuntimeException('The episode dry-run equation does not balance.');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $employees
     * @param  list<array<string, mixed>>  $episodes
     */
    private function assertTraceable(array $employees, array $episodes): void
    {
        $seen = [];
        foreach ($employees as $employee) {
            $pk = (string) $employee['source_pk'];
            if ($pk === '' || isset($seen[$pk])) {
                throw new RuntimeException('An employee classification is missing a unique source key.');
            }
            $seen[$pk] = true;
            if ($employee['mapping_status'] === 'proposed_loadable') {
                if ($employee['display_name_candidate'] === '' || $employee['source_emp_id_no'] === null || $employee['source_emp_id_no'] === '') {
                    throw new RuntimeException('A loadable employee has no display name or employee code.');
                }
                if ($employee['target_company_ulid'] !== self::COMPANY_ID || $employee['source_system'] !== 'legacy_proedge') {
                    throw new RuntimeException('A loadable employee is not mapped to the Proedge test company.');
                }
            }
        }
        foreach ($episodes as $episode) {
            if (! isset($seen[(string) $episode['source_pk']])) {
                throw new RuntimeException('An episode classification has no employee source key.');
            }
            foreach (['proposed_target_joining_date', 'proposed_target_last_working_date'] as $field) {
                if (($episode[$field] ?? null) === '0000-00-00') {
                    throw new RuntimeException('A zero date was proposed as a target date.');
                }
            }
            if ($episode['mapping_status'] === 'proposed_loadable') {
                $status = (string) $episode['episode_candidate_status'];
                if (! in_array($status, ['Open', 'separated'], true) || $episode['proposed_target_joining_date'] === null) {
                    throw new RuntimeException('A loadable episode has no supported status or joining date.');
                }
                if ($status === 'Open' && $episode['proposed_target_last_working_date'] !== null) {
                    throw new RuntimeException('An Open episode proposed a last working date.');
                }
                if ($status === 'separated' && $episode['proposed_target_last_working_date'] === null) {
                    throw new RuntimeException('A separated episode has no leaving date.');
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<array<string, mixed>>
     */
    private function catalogRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            throw new RuntimeException('Classification rows are missing.');
        }

        return array_values(array_filter(
            $rows,
            fn (mixed $row): bool => is_array($row) && (int) ($row['catalog_company_id'] ?? 0) === 24,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $employees
     * @param  list<array<string, mixed>>  $episodes
     * @return array{inserted_employees: int, existing_employees: int, inserted_episodes: int, existing_episodes: int}
     */
    private function import(PDO $pdo, array $employees, array $episodes): array
    {
        $insertedEmployees = 0;
        $existingEmployees = 0;
        $insertedEpisodes = 0;
        $existingEpisodes = 0;
        $employeeIds = [];

        $findEmployee = $pdo->prepare('SELECT id, employee_code, display_name FROM employees WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        $findCode = $pdo->prepare('SELECT source_key FROM employees WHERE company_id = :company AND employee_code = :employee_code');
        $insertEmployee = $pdo->prepare('INSERT INTO employees (id, company_id, employee_code, display_name, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :employee_code, :display_name, :source_system, :source_key, now(), now())');

        foreach ($employees as $employee) {
            if ($employee['mapping_status'] !== 'proposed_loadable') {
                continue;
            }
            $sourceKey = (string) $employee['source_pk'];
            $code = (string) $employee['source_emp_id_no'];
            $name = (string) $employee['display_name_candidate'];
            $findEmployee->execute([
                'company' => self::COMPANY_ID,
                'source_system' => 'legacy_proedge',
                'source_key' => $sourceKey,
            ]);
            $current = $findEmployee->fetch(PDO::FETCH_ASSOC);
            if (is_array($current)) {
                if ($current['employee_code'] !== $code || $current['display_name'] !== $name) {
                    throw new RuntimeException('Existing employee '.$sourceKey.' does not match the classification.');
                }
                $employeeIds[$sourceKey] = (string) $current['id'];
                $existingEmployees++;

                continue;
            }
            $findCode->execute(['company' => self::COMPANY_ID, 'employee_code' => $code]);
            $codeOwner = $findCode->fetchColumn();
            if ($codeOwner !== false) {
                throw new RuntimeException('Employee code '.$code.' is already used by another source row.');
            }
            $id = (string) $employee['proposed_employee_ulid'];
            $insertEmployee->execute([
                'id' => $id,
                'company' => self::COMPANY_ID,
                'employee_code' => $code,
                'display_name' => $name,
                'source_system' => 'legacy_proedge',
                'source_key' => $sourceKey,
            ]);
            $employeeIds[$sourceKey] = $id;
            $insertedEmployees++;
        }

        $findEpisode = $pdo->prepare('SELECT id, employee_id, joining_date::text AS joining_date, last_working_date::text AS last_working_date, episode_status FROM employment_episodes WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        $insertEpisode = $pdo->prepare('INSERT INTO employment_episodes (id, company_id, employee_id, joining_date, last_working_date, episode_status, source_system, source_key, created_at, updated_at) VALUES (:id, :company, :employee_id, :joining_date, :last_working_date, :episode_status, :source_system, :source_key, now(), now())');

        foreach ($episodes as $episode) {
            if ($episode['mapping_status'] !== 'proposed_loadable') {
                continue;
            }
            $sourceKey = (string) $episode['source_pk'];
            if (! isset($employeeIds[$sourceKey])) {
                throw new RuntimeException('A loadable episode has no imported employee.');
            }
            $joining = (string) $episode['proposed_target_joining_date'];
            $leaving = $episode['proposed_target_last_working_date'];
            $status = (string) $episode['episode_candidate_status'];
            $findEpisode->execute([
                'company' => self::COMPANY_ID,
                'source_system' => 'legacy_proedge',
                'source_key' => $sourceKey,
            ]);
            $current = $findEpisode->fetch(PDO::FETCH_ASSOC);
            if (is_array($current)) {
                $sameLeaving = ($current['last_working_date'] ?? null) === $leaving;
                if ($current['employee_id'] !== $employeeIds[$sourceKey] || $current['joining_date'] !== $joining || ! $sameLeaving || $current['episode_status'] !== $status) {
                    throw new RuntimeException('Existing episode '.$sourceKey.' does not match the classification.');
                }
                $existingEpisodes++;

                continue;
            }
            $insertEpisode->execute([
                'id' => (string) $episode['proposed_episode_ulid'],
                'company' => self::COMPANY_ID,
                'employee_id' => $employeeIds[$sourceKey],
                'joining_date' => $joining,
                'last_working_date' => $leaving,
                'episode_status' => $status,
                'source_system' => 'legacy_proedge',
                'source_key' => $sourceKey,
            ]);
            $insertedEpisodes++;
        }

        return [
            'inserted_employees' => $insertedEmployees,
            'existing_employees' => $existingEmployees,
            'inserted_episodes' => $insertedEpisodes,
            'existing_episodes' => $existingEpisodes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $employees
     * @param  list<array<string, mixed>>  $episodes
     */
    private function assertDatabaseMatchesReport(PDO $pdo, array $employees, array $episodes): void
    {
        $this->assertTarget($pdo);
        $loadableEmployees = count(array_filter($employees, fn (array $row): bool => $row['mapping_status'] === 'proposed_loadable'));
        $loadableEpisodes = count(array_filter($episodes, fn (array $row): bool => $row['mapping_status'] === 'proposed_loadable'));
        $storedEmployees = (int) $pdo->query("SELECT count(*) FROM employees WHERE company_id = '".self::COMPANY_ID."'")->fetchColumn();
        $storedEpisodes = (int) $pdo->query("SELECT count(*) FROM employment_episodes WHERE company_id = '".self::COMPANY_ID."'")->fetchColumn();
        if ($storedEmployees !== $loadableEmployees || $storedEpisodes !== $loadableEpisodes) {
            throw new RuntimeException('Stored counts do not match the loadable classification.');
        }
        $duplicateCodes = (int) $pdo->query("SELECT count(*) FROM (SELECT employee_code FROM employees WHERE company_id = '".self::COMPANY_ID."' GROUP BY employee_code HAVING count(*) > 1) duplicates")->fetchColumn();
        $openWithLeaving = (int) $pdo->query("SELECT count(*) FROM employment_episodes WHERE company_id = '".self::COMPANY_ID."' AND episode_status = 'Open' AND last_working_date IS NOT NULL")->fetchColumn();
        $separatedWithoutLeaving = (int) $pdo->query("SELECT count(*) FROM employment_episodes WHERE company_id = '".self::COMPANY_ID."' AND episode_status = 'separated' AND last_working_date IS NULL")->fetchColumn();
        $zeroDates = (int) $pdo->query("SELECT count(*) FROM employment_episodes WHERE company_id = '".self::COMPANY_ID."' AND (joining_date::text = '0000-00-00' OR last_working_date::text = '0000-00-00')")->fetchColumn();
        if ($duplicateCodes !== 0 || $openWithLeaving !== 0 || $separatedWithoutLeaving !== 0 || $zeroDates !== 0) {
            throw new RuntimeException('Imported rows violate employee-code, episode, or zero-date constraints.');
        }

        $episodeKeys = [];
        foreach ($episodes as $episode) {
            if ($episode['mapping_status'] === 'proposed_loadable') {
                $episodeKeys[(string) $episode['source_pk']] = true;
            }
        }
        $missingEpisode = $pdo->prepare('SELECT count(*) FROM employment_episodes WHERE company_id = :company AND source_system = :source_system AND source_key = :source_key');
        foreach ($episodes as $episode) {
            if (isset($episodeKeys[(string) $episode['source_pk']])) {
                continue;
            }
            $missingEpisode->execute([
                'company' => self::COMPANY_ID,
                'source_system' => 'legacy_proedge',
                'source_key' => (string) $episode['source_pk'],
            ]);
            if ((int) $missingEpisode->fetchColumn() !== 0) {
                throw new RuntimeException('A quarantined or no-episode source row has an employment episode.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @param  list<array<string, mixed>>  $employees
     * @param  list<array<string, mixed>>  $episodes
     * @param  array{inserted_employees: int, existing_employees: int, inserted_episodes: int, existing_episodes: int}  $result
     */
    private function report(array $report, array $employees, array $episodes, array $result, string $output): void
    {
        $company = $report['companies'][0];
        $reasons = [];
        foreach ($episodes as $episode) {
            if ($episode['mapping_status'] === 'proposed_loadable') {
                $status = (string) $episode['episode_candidate_status'];
                $reasons['episode_status'][$status] = ($reasons['episode_status'][$status] ?? 0) + 1;

                continue;
            }
            $reasons['episode_disposition'][(string) $episode['disposition']] = ($reasons['episode_disposition'][(string) $episode['disposition']] ?? 0) + 1;
            foreach ($episode['reason_codes'] as $code) {
                $reasons['episode_reasons'][(string) $code] = ($reasons['episode_reasons'][(string) $code] ?? 0) + 1;
            }
        }
        $zero = 0;
        foreach ($episodes as $episode) {
            if ($episode['joining_date_class'] === 'zero' || $episode['discontinue_date_class'] === 'zero') {
                $zero++;
            }
        }
        $this->info('Classification: '.$output);
        $this->info('Employees source '.$company['employees']['source_count'].', proposed load '.$company['employees']['proposed_load'].', quarantined '.$company['employees']['quarantined'].', approved exceptions '.$company['employees']['approved_exceptions'].', unexplained '.$company['employees']['unexplained'].'.');
        $this->info('Episodes source '.$company['episodes']['source_count'].', proposed load '.$company['episodes']['proposed_load'].', quarantined '.$company['episodes']['quarantined'].', approved exceptions '.$company['episodes']['approved_exceptions'].', unexplained '.$company['episodes']['unexplained'].'.');
        $this->info('Episode candidates '.json_encode($reasons['episode_status'] ?? []).'. Non-load episode dispositions '.json_encode($reasons['episode_disposition'] ?? []).'. Reasons '.json_encode($reasons['episode_reasons'] ?? []).'. Zero-date exceptions '.$zero.'.');
        $this->info('Import inserted employees '.$result['inserted_employees'].', existing employees '.$result['existing_employees'].', inserted episodes '.$result['inserted_episodes'].', existing episodes '.$result['existing_episodes'].'.');
    }
}
