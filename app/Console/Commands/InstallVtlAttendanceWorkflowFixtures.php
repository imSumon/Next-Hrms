<?php

namespace App\Console\Commands;

use App\Attendance\AttendanceDayProcessor;
use App\Attendance\LegacyShiftWindow;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchRecordingWriter;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class InstallVtlAttendanceWorkflowFixtures extends Command
{
    private const TARGET_DATABASE = 'proedge_new_hrms_test';

    private const COMPANY_ID = '01M4KG2384JEKYAE5SXC927QDF';

    private const OPERATOR_EMAIL = 'synthetic-attendance-operator@example.test';

    private const SYNTHETIC = 'synthetic_attendance_test';

    private const AUDIT_SOURCE = 'attendance audit citation';

    protected $signature = 'hrms:install-vtl-attendance-workflow-fixtures';

    protected $description = 'Add the minimum labeled VTL attendance fixtures to proedge_new_hrms_test only.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $externalBefore = $this->externalSnapshot();
        $this->useTestDatabase();
        DB::beginTransaction();
        try {
            $this->assertTarget();
            $policyBefore = $this->assertSamplePreserved();
            $user = $this->operator();
            Auth::login($user);
            $this->install();
            $this->assertOutcomes();
            $this->assertPolicyUnchanged($policyBefore);
            $externalAfter = $this->externalSnapshot();
            if ($externalBefore !== $externalAfter) {
                throw new RuntimeException('The app or UAT database changed. The fixture transaction was rolled back.');
            }
            DB::commit();
        } catch (RuntimeException $exception) {
            $this->rollBackFixture($exception->getMessage());

            return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->rollBackFixture($exception->getMessage());

            return self::FAILURE;
        }
        Auth::logout();
        $this->info('VTL attendance workflow fixtures stored in '.self::TARGET_DATABASE.'.');
        $this->info('Legacy employees remain 3 and legacy shifts remain 15. Synthetic rows use source '.self::SYNTHETIC.'.');
        $this->info('next_day 2 keeps the shift end on the work date and moves only the out margin to the next date. Punch margins are not applied. crosses_midnight is true only for next_day '.LegacyShiftWindow::NEXT_DATE.'.');

        return self::SUCCESS;
    }

    private function useTestDatabase(): void
    {
        config([
            'database.connections.pgsql.url' => null,
            'database.connections.pgsql.host' => '127.0.0.1',
            'database.connections.pgsql.port' => '5432',
            'database.connections.pgsql.database' => self::TARGET_DATABASE,
        ]);
        DB::purge('pgsql');
        DB::reconnect('pgsql');
    }

    private function assertTarget(): void
    {
        $meta = DB::selectOne('SELECT current_database() AS db, inet_server_addr()::text AS addr, inet_server_port() AS port');
        if ($meta === null || $meta->db !== self::TARGET_DATABASE || ! str_starts_with((string) $meta->addr, '127.0.0.1') || (int) $meta->port !== 5432) {
            throw new RuntimeException('Refusing to write because the connection is not local '.self::TARGET_DATABASE.'.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function assertSamplePreserved(): array
    {
        $company = Company::query()->find(self::COMPANY_ID);
        if (! $company instanceof Company || $company->company_name !== 'Viyellatex Group' || $company->source_system !== null || $company->source_key !== '20') {
            throw new RuntimeException('The VTL sample company is missing or has changed.');
        }
        $legacyEmployees = Employee::query()->where('company_id', $company->id)->where('source_system', 'legacy_vtlhrm')->count();
        $legacyShifts = Shift::query()->where('company_id', $company->id)->where('source_system', 'legacy_vtlhrm')->count();
        $proedgeEmployees = Employee::query()->where('source_system', 'legacy_proedge')->count();
        if ($legacyEmployees !== 3 || $legacyShifts !== 15 || $proedgeEmployees !== 160) {
            throw new RuntimeException('The existing VTL sample or Proedge test employees are not intact.');
        }
        $policy = AttendancePolicy::query()->where('company_id', $company->id)->first();
        if (! $policy instanceof AttendancePolicy) {
            throw new RuntimeException('The VTL attendance policy is missing.');
        }

        return $policy->only([
            'timezone',
            'shift_winner',
            'punch_selection',
            'grace_seconds',
            'late_measured_from',
            'early_threshold_seconds',
            'present_status_code',
            'incomplete_status_code',
            'correction_fields',
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function assertPolicyUnchanged(array $before): void
    {
        $policy = AttendancePolicy::query()->where('company_id', self::COMPANY_ID)->first();
        if (! $policy instanceof AttendancePolicy || $policy->only(array_keys($before)) !== $before) {
            throw new RuntimeException('The VTL attendance policy changed.');
        }
        if ($before['shift_winner'] !== 'roster' || $before['punch_selection'] !== 'first_and_last' || $before['grace_seconds'] !== 0 || $before['correction_fields'] !== '') {
            throw new RuntimeException('The stored VTL attendance policy is not the expected test configuration.');
        }
    }

    private function operator(): User
    {
        $user = User::query()->where('email', self::OPERATOR_EMAIL)->first();
        if (! $user instanceof User) {
            $user = User::query()->create([
                'name' => 'Synthetic Attendance Operator',
                'email' => self::OPERATOR_EMAIL,
                'password' => bin2hex(random_bytes(24)),
            ]);
        }
        CompanyMembership::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => self::COMPANY_ID,
            ],
            [
                'status' => 'active',
                'can_manage_employees' => false,
                'can_manage_organization_masters' => false,
            ],
        );
        PunchRecordingWriter::query()->updateOrInsert(
            [
                'user_id' => $user->id,
                'company_id' => self::COMPANY_ID,
            ],
            [
                'authorized_at' => now(),
            ],
        );
        $password = $_ENV['HRMS_TEST_OPERATOR_PASSWORD'] ?? getenv('HRMS_TEST_OPERATOR_PASSWORD');
        if (is_string($password) && strlen($password) >= 12) {
            $user->password = $password;
            $user->save();
        }

        return $user;
    }

    private function install(): void
    {
        $employees = $this->legacyEmployees();
        $corporate = $this->legacyShift('14');
        $vsl = $this->legacyShift('16');
        $night = $this->legacyShift('12');
        $this->assertWindow($corporate, false);
        $this->assertWindow($night, true);
        $synthetic = $this->syntheticEmployee($night);
        $this->roster($employees['463'], $corporate, '2026-09-02', '463:2026-09-02');
        $this->roster($employees['100450'], $corporate, '2026-09-03', '100450:2026-09-03');
        $this->roster($employees['100654'], $vsl, '2026-09-13', '100654:2026-09-13');
        $this->roster($synthetic, $night, '2026-09-04', 'night-1:2026-09-04');
        $this->punch($employees['463'], '126065', self::AUDIT_SOURCE, '2026-09-02T08:17:41+06:00', 'audit:463:2026-09-02T08:17:41');
        $this->punch($employees['463'], '126065', self::SYNTHETIC, '2026-09-02T12:00:00+06:00', 'synthetic:463:2026-09-02T12:00:00');
        $this->punch($employees['463'], '126065', self::AUDIT_SOURCE, '2026-09-02T17:34:36+06:00', 'audit:463:2026-09-02T17:34:36');
        $this->punch($employees['100450'], '03129487', self::SYNTHETIC, '2026-09-03T09:10:59+06:00', 'synthetic:100450:2026-09-03T09:10:59');
        $this->punch($employees['100450'], '03129487', self::SYNTHETIC, '2026-09-03T17:40:00+06:00', 'synthetic:100450:2026-09-03T17:40:00');
        $this->punch($employees['100654'], '05000356', self::SYNTHETIC, '2026-09-13T08:00:00+06:00', 'synthetic:100654:2026-09-13T08:00:00');
        $this->punch($synthetic, 'SYN-VTL-NIGHT', self::SYNTHETIC, '2026-09-04T22:30:59+06:00', 'synthetic:night-1:2026-09-04T22:30:59');
        $this->punch($synthetic, 'SYN-VTL-NIGHT', self::SYNTHETIC, '2026-09-05T06:00:00+06:00', 'synthetic:night-1:2026-09-05T06:00:00');
        foreach ([
            [$employees['463'], '2026-09-02'],
            [$employees['100450'], '2026-09-03'],
            [$employees['100654'], '2026-09-13'],
            [$synthetic, '2026-09-04'],
        ] as [$employee, $workDate]) {
            $this->calculateOnce($employee, $workDate);
        }
        $this->installPolicyWorkshop();
    }

    /**
     * @return array<string, Employee>
     */
    private function legacyEmployees(): array
    {
        $found = [];
        foreach (['463' => '03123173', '100450' => '03129487', '100654' => '05000356'] as $sourceKey => $code) {
            $employee = Employee::query()
                ->where('company_id', self::COMPANY_ID)
                ->where('source_system', 'legacy_vtlhrm')
                ->where('source_key', $sourceKey)
                ->first();
            if (! $employee instanceof Employee || $employee->employee_code !== $code) {
                throw new RuntimeException('Legacy VTL employee '.$sourceKey.' is missing.');
            }
            $found[$sourceKey] = $employee;
        }

        return $found;
    }

    private function legacyShift(string $sourceKey): Shift
    {
        $shift = Shift::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('source_system', 'legacy_vtlhrm')
            ->where('source_key', $sourceKey)
            ->first();
        if (! $shift instanceof Shift) {
            throw new RuntimeException('Legacy VTL shift '.$sourceKey.' is missing.');
        }

        return $shift;
    }

    private function assertWindow(Shift $shift, bool $crossesMidnight): void
    {
        $version = ShiftVersion::query()->where('company_id', self::COMPANY_ID)->where('shift_id', $shift->id)->first();
        if (! $version instanceof ShiftVersion || $version->crosses_midnight !== $crossesMidnight) {
            throw new RuntimeException('Shift '.$shift->source_key.' does not have the documented midnight flag.');
        }
    }

    private function syntheticEmployee(Shift $night): Employee
    {
        $existing = Employee::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('source_system', self::SYNTHETIC)
            ->where('source_key', 'night-1')
            ->first();
        if ($existing instanceof Employee) {
            return $existing;
        }
        $id = strtoupper((string) Str::ulid());
        DB::table('employees')->insert([
            'id' => $id,
            'company_id' => self::COMPANY_ID,
            'employee_code' => 'SYN-VTL-NIGHT',
            'display_name' => 'Synthetic Night Operator',
            'source_system' => self::SYNTHETIC,
            'source_key' => 'night-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $employee = Employee::query()->find($id);
        if (! $employee instanceof Employee) {
            throw new RuntimeException('The synthetic night employee was not stored.');
        }
        ShiftAssignmentCandidate::query()->create([
            'company_id' => self::COMPANY_ID,
            'employee_id' => $employee->id,
            'shift_id' => $night->id,
            'candidate_kind' => 'default',
            'roster_work_date' => null,
            'source_system' => self::SYNTHETIC,
            'source_key' => 'night-1:default',
        ]);

        return $employee;
    }

    private function roster(Employee $employee, Shift $shift, string $workDate, string $sourceKey): void
    {
        $existing = ShiftAssignmentCandidate::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('employee_id', $employee->id)
            ->where('candidate_kind', 'roster')
            ->whereDate('roster_work_date', $workDate)
            ->first();
        if ($existing instanceof ShiftAssignmentCandidate) {
            if ($existing->shift_id !== $shift->id || $existing->source_system !== self::SYNTHETIC) {
                throw new RuntimeException('A roster row already exists for '.$workDate.' and is not the synthetic fixture.');
            }

            return;
        }
        ShiftAssignmentCandidate::query()->create([
            'company_id' => self::COMPANY_ID,
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'candidate_kind' => 'roster',
            'roster_work_date' => $workDate,
            'source_system' => self::SYNTHETIC,
            'source_key' => $sourceKey,
        ]);
    }

    private function punch(Employee $employee, string $identity, string $sourceName, string $eventAt, string $sourceEventId): void
    {
        $existing = PunchEvent::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('source_event_id', $sourceEventId)
            ->first();
        if ($existing instanceof PunchEvent) {
            return;
        }
        app(PunchRecordingRegistry::class)->record(self::COMPANY_ID, [
            'employee_id' => $employee->id,
            'identity_value' => $identity,
            'source_name' => $sourceName,
            'event_at' => $eventAt,
            'source_event_id' => $sourceEventId,
        ]);
    }

    private function calculateOnce(Employee $employee, string $workDate): void
    {
        $exists = AttendanceDayResult::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->exists();
        if ($exists) {
            return;
        }
        app(AttendanceDayProcessor::class)->calculate(self::COMPANY_ID, $employee->id, $workDate);
    }

    private function installPolicyWorkshop(): void
    {
        $company = Company::query()
            ->where('source_system', self::SYNTHETIC)
            ->where('source_key', 'policy-workshop')
            ->first();
        if (! $company instanceof Company) {
            $id = strtoupper((string) Str::ulid());
            DB::table('companies')->insert([
                'id' => $id,
                'company_code' => null,
                'company_name' => 'Synthetic Attendance Policy Workshop',
                'status' => 'Active',
                'source_system' => self::SYNTHETIC,
                'source_key' => 'policy-workshop',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $company = Company::query()->find($id);
        }
        if (! $company instanceof Company || $company->company_name !== 'Synthetic Attendance Policy Workshop') {
            throw new RuntimeException('The synthetic policy workshop company is missing.');
        }
        $policy = AttendancePolicy::query()->where('company_id', $company->id)->first();
        if (! $policy instanceof AttendancePolicy) {
            AttendancePolicy::query()->create([
                'company_id' => $company->id,
                'timezone' => 'Asia/Dhaka',
                'shift_winner' => 'default',
                'punch_selection' => 'first_and_last',
                'grace_seconds' => 0,
                'late_measured_from' => 'shift_start',
                'early_threshold_seconds' => null,
                'present_status_code' => 'P',
                'incomplete_status_code' => 'A',
                'correction_fields' => 'status_code',
            ]);
        } elseif ($policy->correction_fields !== 'status_code' || $policy->shift_winner !== 'default') {
            throw new RuntimeException('The synthetic policy workshop already has a different policy.');
        }
        $employee = Employee::query()
            ->where('company_id', $company->id)
            ->where('source_system', self::SYNTHETIC)
            ->where('source_key', 'policy-1')
            ->first();
        if (! $employee instanceof Employee) {
            $employeeId = strtoupper((string) Str::ulid());
            DB::table('employees')->insert([
                'id' => $employeeId,
                'company_id' => $company->id,
                'employee_code' => 'SYN-POLICY-1',
                'display_name' => 'Synthetic Policy Employee',
                'source_system' => self::SYNTHETIC,
                'source_key' => 'policy-1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $employee = Employee::query()->find($employeeId);
        }
        if (! $employee instanceof Employee) {
            throw new RuntimeException('The synthetic policy employee is missing.');
        }
        $shift = Shift::query()->where('company_id', $company->id)->where('source_key', 'policy-day')->first();
        if (! $shift instanceof Shift) {
            $shift = Shift::query()->create([
                'company_id' => $company->id,
                'name' => 'Synthetic policy day',
                'source_system' => self::SYNTHETIC,
                'source_key' => 'policy-day',
            ]);
            ShiftVersion::query()->create([
                'company_id' => $company->id,
                'shift_id' => $shift->id,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'crosses_midnight' => false,
                'effective_from' => '2026-09-01',
                'effective_to' => '2026-09-30',
                'source_system' => self::SYNTHETIC,
                'source_key' => 'policy-day',
            ]);
        }
        ShiftAssignmentCandidate::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'candidate_kind' => 'default',
            ],
            [
                'shift_id' => $shift->id,
                'roster_work_date' => null,
                'source_system' => self::SYNTHETIC,
                'source_key' => 'policy-1:default',
            ],
        );
        $this->workshopPunch($company, $employee, '2026-09-08T09:00:00+06:00', 'synthetic:policy-1:2026-09-08T09:00:00');
        $this->workshopPunch($company, $employee, '2026-09-08T17:00:00+06:00', 'synthetic:policy-1:2026-09-08T17:00:00');
        $this->workshopPunch($company, $employee, '2026-09-09T09:00:00+06:00', 'synthetic:policy-1:2026-09-09T09:00:00');
        $this->workshopPunch($company, $employee, '2026-09-09T17:00:00+06:00', 'synthetic:policy-1:2026-09-09T17:00:00');
        foreach (['2026-09-08', '2026-09-09'] as $workDate) {
            $exists = AttendanceDayResult::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $employee->id)
                ->whereDate('work_date', $workDate)
                ->exists();
            if (! $exists) {
                app(AttendanceDayProcessor::class)->calculate($company->id, $employee->id, $workDate);
            }
        }
        $user = User::query()->where('email', self::OPERATOR_EMAIL)->first();
        if (! $user instanceof User) {
            throw new RuntimeException('The synthetic attendance operator is missing.');
        }
        CompanyMembership::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => $company->id,
            ],
            [
                'status' => 'active',
                'can_manage_employees' => false,
                'can_manage_organization_masters' => false,
            ],
        );
        PunchRecordingWriter::query()->updateOrInsert(
            [
                'user_id' => $user->id,
                'company_id' => $company->id,
            ],
            [
                'authorized_at' => now(),
            ],
        );
        $vtlPolicy = AttendancePolicy::query()->where('company_id', self::COMPANY_ID)->value('correction_fields');
        if ($vtlPolicy !== '') {
            throw new RuntimeException('The VTL attendance policy changed while preparing the synthetic workshop.');
        }
    }

    private function workshopPunch(Company $company, Employee $employee, string $eventAt, string $sourceEventId): void
    {
        $existing = PunchEvent::query()
            ->where('company_id', $company->id)
            ->where('source_event_id', $sourceEventId)
            ->exists();
        if ($existing) {
            return;
        }
        app(PunchRecordingRegistry::class)->record($company->id, [
            'employee_id' => $employee->id,
            'identity_value' => $employee->employee_code,
            'source_name' => self::SYNTHETIC,
            'event_at' => $eventAt,
            'source_event_id' => $sourceEventId,
        ]);
    }

    private function assertOutcomes(): void
    {
        $employees = $this->legacyEmployees();
        $normal = $this->current($employees['463'], '2026-09-02');
        $late = $this->current($employees['100450'], '2026-09-03');
        $incomplete = $this->current($employees['100654'], '2026-09-13');
        $nightEmployee = Employee::query()->where('company_id', self::COMPANY_ID)->where('source_key', 'night-1')->first();
        if (! $nightEmployee instanceof Employee) {
            throw new RuntimeException('The synthetic night employee is missing.');
        }
        $night = $this->current($nightEmployee, '2026-09-04');
        $this->assertDay($normal, false, 'P', 0, '08:17:41', '17:34:36');
        $this->assertDay($late, false, 'P', 600, '09:10:59', '17:40:00');
        $this->assertDay($incomplete, true, 'A', 0, '08:00:00', null);
        $this->assertDay($night, false, 'P', 0, '22:30:59', '06:00:00');
        $middle = PunchEvent::query()->where('source_event_id', 'synthetic:463:2026-09-02T12:00:00')->first();
        if (! $middle instanceof PunchEvent || $normal->selected_in_event_id === $middle->id || $normal->selected_out_event_id === $middle->id) {
            throw new RuntimeException('The synthetic middle punch was paired.');
        }
        $legacyEmployees = Employee::query()->where('company_id', self::COMPANY_ID)->where('source_system', 'legacy_vtlhrm')->count();
        $legacyShifts = Shift::query()->where('company_id', self::COMPANY_ID)->where('source_system', 'legacy_vtlhrm')->count();
        $syntheticEmployees = Employee::query()->where('company_id', self::COMPANY_ID)->where('source_system', self::SYNTHETIC)->count();
        if ($legacyEmployees !== 3 || $legacyShifts !== 15 || $syntheticEmployees !== 1) {
            throw new RuntimeException('The fixture changed the VTL sample counts.');
        }
        if (! LegacyShiftWindow::crossesMidnight(LegacyShiftWindow::NEXT_DATE) || LegacyShiftWindow::crossesMidnight(LegacyShiftWindow::OUT_MARGIN_NEXT_DATE) || LegacyShiftWindow::punchMarginSupported()) {
            throw new RuntimeException('The documented next_day meaning changed.');
        }
    }

    private function current(Employee $employee, string $workDate): AttendanceDayResultVersion
    {
        $pointer = AttendanceDayResult::query()
            ->where('company_id', self::COMPANY_ID)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->first();
        $version = $pointer instanceof AttendanceDayResult
            ? AttendanceDayResultVersion::query()->find($pointer->current_version_id)
            : null;
        if (! $version instanceof AttendanceDayResultVersion) {
            throw new RuntimeException('Day '.$workDate.' has no result.');
        }

        return $version;
    }

    private function assertDay(AttendanceDayResultVersion $version, bool $incomplete, string $status, int $lateSeconds, string $inTime, ?string $outTime): void
    {
        $in = $this->localTime($version->selected_in_event_id);
        $out = $version->selected_out_event_id === null ? null : $this->localTime($version->selected_out_event_id);
        if ($version->incomplete !== $incomplete || $version->status_code !== $status || $version->late_seconds !== $lateSeconds || $in !== $inTime || $out !== $outTime) {
            throw new RuntimeException('Day '.$version->work_date?->toDateString().' did not match the fixture expectation.');
        }
    }

    private function localTime(?string $eventId): string
    {
        $event = PunchEvent::query()->find($eventId);
        if (! $event instanceof PunchEvent || $event->event_at === null) {
            throw new RuntimeException('A selected punch is missing.');
        }

        return $event->event_at->timezone('Asia/Dhaka')->format('H:i:s');
    }

    /**
     * @return array{app_employees: int, app_attendance_tables: int, app_punches: int, uat_employees: int, uat_catalog_20: int}
     */
    private function externalSnapshot(): array
    {
        return [
            'app_employees' => $this->readOnlyCount('proedge_new_hrms', 'SELECT count(*) FROM employees'),
            'app_attendance_tables' => $this->readOnlyCount('proedge_new_hrms', "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'attendance_policies'"),
            'app_punches' => $this->readOnlyCount('proedge_new_hrms', 'SELECT count(*) FROM punch_events'),
            'uat_employees' => $this->readOnlyCount('proedge_new_hrms_uat', 'SELECT count(*) FROM employees'),
            'uat_catalog_20' => $this->readOnlyCount('proedge_new_hrms_uat', "SELECT count(*) FROM companies WHERE source_key = '20'"),
        ];
    }

    private function readOnlyCount(string $database, string $sql): int
    {
        $pdo = new PDO(
            'pgsql:host=127.0.0.1;port=5432;dbname='.$database,
            (string) config('database.connections.pgsql.username'),
            (string) config('database.connections.pgsql.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $meta = $pdo->query('SELECT current_database() AS db, inet_server_addr()::text AS addr')->fetch(PDO::FETCH_ASSOC);
        if (! is_array($meta) || $meta['db'] !== $database || ! str_starts_with((string) $meta['addr'], '127.0.0.1')) {
            throw new RuntimeException('Refusing to read because the connection is not local '.$database.'.');
        }

        return (int) $pdo->query($sql)->fetchColumn();
    }

    private function rollBackFixture(string $message): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Auth::logout();
        $this->error($message);
    }
}
