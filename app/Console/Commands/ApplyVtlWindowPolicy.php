<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class ApplyVtlWindowPolicy extends Command
{
    private const TARGET_DATABASE = 'proedge_new_hrms_test';

    private const COMPANY_ID = '01M4KG2384JEKYAE5SXC927QDF';

    /**
     * @var array<string, string>
     */
    private const WINDOW_POLICY = [
        'punch_selection' => 'margin_first_and_last',
        'working_day_code' => 'W',
        'non_working_day_code' => 'H',
        'non_working_status_code' => 'H',
        'non_working_attended_status_code' => 'HP',
    ];

    protected $signature = 'hrms:vtl-window-policy {--apply : Store the approved window policy for VTL in the test database}';

    protected $description = 'Preview, then optionally apply, the owner-approved VTL calendar window rule in proedge_new_hrms_test only. No day is recalculated.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }
        $pdo = new PDO(
            'pgsql:host=127.0.0.1;port=5432;dbname='.self::TARGET_DATABASE,
            (string) config('database.connections.pgsql.username'),
            (string) config('database.connections.pgsql.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->exec("SET TIME ZONE 'UTC'");
        $meta = $pdo->query('SELECT current_database() AS db, inet_server_addr()::text AS addr')->fetch(PDO::FETCH_ASSOC);
        if (! is_array($meta) || $meta['db'] !== self::TARGET_DATABASE || ! str_starts_with((string) $meta['addr'], '127.0.0.1')) {
            $this->error('Refusing: the connection is not local '.self::TARGET_DATABASE.'.');

            return self::FAILURE;
        }
        $policy = $pdo->query("SELECT * FROM attendance_policies WHERE company_id = '".self::COMPANY_ID."'")->fetch(PDO::FETCH_ASSOC);
        if (! is_array($policy) || $policy['correction_fields'] !== '' || $this->truthy($policy['missing_punch_requests'])) {
            $this->error('The VTL policy is missing or has settings this command does not expect.');

            return self::FAILURE;
        }
        $this->preview($pdo);
        if (! $this->option('apply')) {
            $this->info('Preview only. The VTL policy was not changed.');

            return self::SUCCESS;
        }
        if ($policy['punch_selection'] === self::WINDOW_POLICY['punch_selection']) {
            $this->info('The VTL window policy is already stored. Nothing changed.');

            return self::SUCCESS;
        }
        if ($policy['punch_selection'] !== 'first_and_last') {
            $this->error('The VTL policy has an unexpected punch selection.');

            return self::FAILURE;
        }
        $pdo->beginTransaction();
        try {
            $versionsBefore = $this->count($pdo, 'attendance_day_result_versions');
            $punchesBefore = $this->count($pdo, 'punch_events');
            $update = $pdo->prepare('UPDATE attendance_policies SET punch_selection = :punch_selection, working_day_code = :working_day_code, non_working_day_code = :non_working_day_code, non_working_status_code = :non_working_status_code, non_working_attended_status_code = :non_working_attended_status_code, updated_at = now() WHERE company_id = :company');
            $update->execute(self::WINDOW_POLICY + ['company' => self::COMPANY_ID]);
            $audit = $pdo->prepare("INSERT INTO audit_events (id, company_id, subject_kind, subject_id, action_kind, acting_user_id, acted_at, previous_value, new_value, reason, created_at) VALUES (:id, :company, 'attendance_policy', :subject, 'attendance_policy_window_rule_applied', NULL, now(), :previous, :new, :reason, now())");
            $audit->execute([
                'id' => strtoupper((string) Str::ulid()),
                'company' => self::COMPANY_ID,
                'subject' => (string) $policy['id'],
                'previous' => json_encode(array_intersect_key($policy, self::WINDOW_POLICY)),
                'new' => json_encode(self::WINDOW_POLICY),
                'reason' => 'Owner-approved VTL calendar window rule for the local test database. Existing results were not recalculated.',
            ]);
            if ($this->count($pdo, 'attendance_day_result_versions') !== $versionsBefore || $this->count($pdo, 'punch_events') !== $punchesBefore) {
                throw new RuntimeException('The policy change touched results or punches.');
            }
            $pdo->commit();
        } catch (RuntimeException $exception) {
            $pdo->rollBack();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info('VTL window policy stored in '.self::TARGET_DATABASE.'. No day was recalculated.');

        return self::SUCCESS;
    }

    /**
     * Predict each September calendar day under the window rule without writing.
     */
    private function preview(PDO $pdo): void
    {
        $days = $pdo->query("SELECT r.id, e.employee_code, r.employee_id, r.work_date::text AS work_date, r.source_day_code, r.always_present, r.window_starts_at, r.window_ends_at, r.shift_starts_at, r.shift_ends_at, r.absent_after_at FROM roster_source_days r JOIN employees e ON e.company_id = r.company_id AND e.id = r.employee_id WHERE r.company_id = '".self::COMPANY_ID."' AND r.source_system = 'legacy_vtlhrm' ORDER BY e.employee_code, r.work_date")->fetchAll(PDO::FETCH_ASSOC);
        $punches = $pdo->prepare('SELECT p.event_at, p.source_name FROM punch_events p JOIN punch_identities i ON i.company_id = p.company_id AND i.id = p.punch_identity_id WHERE p.company_id = :company AND i.employee_id = :employee AND p.event_at BETWEEN :from AND :to ORDER BY p.event_at, p.id');
        $current = $pdo->prepare('SELECT v.status_code, v.late_seconds FROM attendance_day_results d JOIN attendance_day_result_versions v ON v.company_id = d.company_id AND v.id = d.current_version_id WHERE d.company_id = :company AND d.employee_id = :employee AND d.work_date = :work_date');
        $summary = [];
        $processed = [];
        foreach ($days as $day) {
            $punches->execute(['company' => self::COMPANY_ID, 'employee' => $day['employee_id'], 'from' => $day['window_starts_at'], 'to' => $day['window_ends_at']]);
            $events = $punches->fetchAll(PDO::FETCH_ASSOC);
            $predicted = $this->predict($day, $events);
            $row = $summary[$day['employee_code']] ?? ['W' => 0, 'H' => 0, 'with_punches' => 0, 'P' => 0, 'A' => 0, 'H_status' => 0, 'HP' => 0, 'processed' => 0];
            $row[$day['source_day_code']]++;
            $row['with_punches'] += $events === [] ? 0 : 1;
            $row[$predicted === 'H' ? 'H_status' : $predicted]++;
            $current->execute(['company' => self::COMPANY_ID, 'employee' => $day['employee_id'], 'work_date' => $day['work_date']]);
            $existing = $current->fetch(PDO::FETCH_ASSOC);
            if (is_array($existing)) {
                $row['processed']++;
                $sources = implode(', ', array_unique(array_column($events, 'source_name')));
                $processed[] = sprintf('%s %s current=%s predicted=%s punches=%d (%s)', $day['employee_code'], $day['work_date'], $existing['status_code'], $predicted, count($events), $sources === '' ? 'none' : $sources);
            }
            $summary[$day['employee_code']] = $row;
        }
        foreach ($summary as $code => $row) {
            $this->line(sprintf('%s calendar W=%d H=%d with_punches=%d predicted P=%d A=%d H=%d HP=%d already_processed=%d', $code, $row['W'], $row['H'], $row['with_punches'], $row['P'], $row['A'], $row['H_status'], $row['HP'], $row['processed']));
        }
        foreach ($processed as $line) {
            $this->line('existing '.$line);
        }
        $this->line('Predicted A on a W day without punches reflects the missing legacy raw log, not proven absence.');
    }

    /**
     * @param  array<string, mixed>  $day
     * @param  list<array<string, mixed>>  $events
     */
    private function predict(array $day, array $events): string
    {
        $nonWorking = $day['source_day_code'] === self::WINDOW_POLICY['non_working_day_code'];
        $absent = $nonWorking ? 'H' : 'A';
        $attended = $nonWorking ? 'HP' : 'P';
        if ($this->truthy($day['always_present'])) {
            return $nonWorking ? 'H' : 'P';
        }
        if (count($events) < 2) {
            return $absent;
        }
        $in = strtotime((string) $events[0]['event_at']);
        $out = strtotime((string) $events[count($events) - 1]['event_at']);
        $left = $out >= strtotime((string) $day['shift_ends_at']) && $out <= strtotime((string) $day['window_ends_at']);
        $arrived = $in >= strtotime((string) $day['window_starts_at']) && $in <= strtotime((string) $day['absent_after_at']);

        return $left && $arrived ? $attended : $absent;
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, 't', 'true', '1'], true);
    }

    private function count(PDO $pdo, string $table): int
    {
        return (int) $pdo->query('SELECT count(*) FROM '.$table." WHERE company_id = '".self::COMPANY_ID."'")->fetchColumn();
    }
}
