<?php

namespace Tests\Feature;

use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchRecordingWriter;
use App\Punch\DeviceEventIngestion;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\EmployeeDatabaseTestCase;

class DevicePunchBatchImportTest extends EmployeeDatabaseTestCase
{
    private ?string $deviceLogFixturePath = null;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('device_log_fixture');
        DB::purge('device_log_fixture_setup');

        if ($this->deviceLogFixturePath !== null && is_file($this->deviceLogFixturePath)) {
            @unlink($this->deviceLogFixturePath);
        }

        parent::tearDown();
    }

    public function test_unconfirmed_batch_returns_a_fingerprint_and_writes_nothing(): void
    {
        $setup = $this->scenario();
        $punchesBefore = PunchEvent::query()->whereIn('company_id', [$setup['own']->id, $setup['other']->id])->count();

        $response = $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-03',
        ])->assertOk();

        $this->assertFalse($response->json('import_confirmed'));
        $this->assertFalse($response->json('writes_enabled'));
        $this->assertFalse($response->json('durable_exception_storage'));
        $this->assertSame('not_confirmed', $response->json('status'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $response->json('fingerprint'));
        $this->assertSame('importable', collect($response->json('rows'))->firstWhere('source_event_id', 'all_att_log:1')['outcome']);
        $this->assertSame('2026-09-02 08:10:00', collect($response->json('rows'))->firstWhere('source_event_id', 'all_att_log:1')['source_wall_time']);
        $this->assertSame($punchesBefore, PunchEvent::query()->whereIn('company_id', [$setup['own']->id, $setup['other']->id])->count());
    }

    public function test_confirmed_batch_sends_raw_source_time_through_ingest_and_does_not_record_naive_rows(): void
    {
        $setup = $this->scenario();
        $punchesBefore = PunchEvent::query()
            ->whereIn('company_id', [$setup['own']->id, $setup['other']->id])
            ->orderBy('id')
            ->get(['id', 'company_id', 'source_event_id', 'source_name', 'event_at'])
            ->toArray();
        $fingerprint = $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-03',
        ])->assertOk()->json('fingerprint');

        $response = $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-03',
            'confirm' => true,
            'fingerprint' => $fingerprint,
        ])->assertOk();

        $rows = collect($response->json('rows'))->keyBy('source_event_id');
        $this->assertTrue($response->json('import_confirmed'));
        $this->assertFalse($response->json('writes_enabled'));
        $this->assertSame('completed_with_exceptions', $response->json('status'));
        $this->assertSame('invalid_timestamp', $rows['all_att_log:1']['outcome']);
        $this->assertSame('2026-09-02 08:10:00', $rows['all_att_log:1']['source_wall_time']);
        $this->assertNull($rows['all_att_log:1']['event_id']);
        $this->assertStringContainsString('UTC offset', (string) $rows['all_att_log:1']['message']);
        $this->assertSame('already_recorded', $rows['all_att_log:2']['outcome']);
        $this->assertSame('possible_duplicate', $rows['all_att_log:3']['outcome']);
        $this->assertSame('unmapped', $rows['all_att_log:4']['outcome']);
        $this->assertSame('ambiguous', $rows['all_att_log:5']['outcome']);
        $this->assertSame(0, $response->json('counts.recorded'));
        $this->assertSame(
            $punchesBefore,
            PunchEvent::query()
                ->whereIn('company_id', [$setup['own']->id, $setup['other']->id])
                ->orderBy('id')
                ->get(['id', 'company_id', 'source_event_id', 'source_name', 'event_at'])
                ->toArray()
        );
        $this->assertSame(
            ['all_att_log:2'],
            PunchEvent::query()
                ->where('company_id', $setup['own']->id)
                ->where('source_name', (string) config('attendance.device_log.source_name'))
                ->pluck('source_event_id')
                ->all()
        );
    }

    public function test_a_confirmed_retry_is_idempotent(): void
    {
        $setup = $this->scenario();
        $payload = [
            'from' => '2026-09-02',
            'to' => '2026-09-03',
            'confirm' => true,
            'fingerprint' => $this->postJson('/api/attendance/device-log/import', [
                'from' => '2026-09-02',
                'to' => '2026-09-03',
            ])->assertOk()->json('fingerprint'),
        ];
        $before = PunchEvent::query()->where('company_id', $setup['own']->id)->orderBy('id')->get(['id', 'source_event_id', 'event_at'])->toArray();

        $first = $this->postJson('/api/attendance/device-log/import', $payload)->assertOk();
        $second = $this->postJson('/api/attendance/device-log/import', $payload)->assertOk();

        $this->assertSame($first->json('counts'), $second->json('counts'));
        $this->assertSame($first->json('rows'), $second->json('rows'));
        $this->assertSame($before, PunchEvent::query()->where('company_id', $setup['own']->id)->orderBy('id')->get(['id', 'source_event_id', 'event_at'])->toArray());
        $this->assertSame(['all_att_log:2'], PunchEvent::query()->where('company_id', $setup['own']->id)->where('source_name', (string) config('attendance.device_log.source_name'))->pluck('source_event_id')->all());
    }

    public function test_a_changed_fingerprint_refuses_every_write(): void
    {
        $setup = $this->scenario();
        $fingerprint = $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-03',
        ])->assertOk()->json('fingerprint');
        DB::connection('device_log_fixture_setup')->table('all_att_log')->insert([
            'id' => 11,
            'date' => '2026-09-02 11:22:00',
            'emp_code' => 'CARD-1',
            'area' => null,
        ]);

        $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-03',
            'confirm' => true,
            'fingerprint' => $fingerprint,
        ])->assertUnprocessable()->assertJsonValidationErrors(['fingerprint']);

        $this->assertSame(
            ['all_att_log:2'],
            PunchEvent::query()
                ->where('company_id', $setup['own']->id)
                ->where('source_name', (string) config('attendance.device_log.source_name'))
                ->pluck('source_event_id')
                ->all()
        );
    }

    public function test_import_needs_a_session_and_the_writer_grant(): void
    {
        $this->scenario();
        auth()->logout();
        $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-02',
        ])->assertUnauthorized();

        $company = Company::query()->create(['company_name' => 'Harbor Batch No Grant', 'status' => 'Active']);
        $this->signInFor($company, false);
        $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-02',
        ])->assertForbidden();
    }

    public function test_an_unconfigured_source_does_not_import(): void
    {
        config(['attendance.device_log.connection' => null]);
        $company = Company::query()->create(['company_name' => 'Harbor Batch Unconfigured', 'status' => 'Active']);
        $this->signInAsWriter($company);

        $this->postJson('/api/attendance/device-log/import', [
            'from' => '2026-09-02',
            'to' => '2026-09-02',
            'confirm' => true,
            'fingerprint' => str_repeat('a', 64),
        ])->assertOk()
            ->assertJsonPath('source.configured', false)
            ->assertJsonPath('writes_enabled', false)
            ->assertJsonPath('import_confirmed', false)
            ->assertJsonMissingPath('rows');
    }

    /**
     * @return array{own: Company, other: Company}
     */
    private function scenario(): array
    {
        $this->deviceLogFixturePath = tempnam(sys_get_temp_dir(), 'hrms-device-log-');

        if ($this->deviceLogFixturePath === false) {
            $this->deviceLogFixturePath = null;
            throw new \RuntimeException('Unable to create device log test fixture.');
        }

        $fixtureConnection = [
            'driver' => 'sqlite',
            'database' => $this->deviceLogFixturePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ];

        config([
            'database.connections.device_log_fixture_setup' => $fixtureConnection,
            'database.connections.device_log_fixture' => $fixtureConnection,
            'attendance.device_log.connection' => 'device_log_fixture',
        ]);
        DB::purge('device_log_fixture_setup');
        DB::purge('device_log_fixture');
        Schema::connection('device_log_fixture_setup')->create('all_att_log', function ($table): void {
            $table->integer('id')->primary();
            $table->string('date');
            $table->string('emp_code');
            $table->string('update_datetime')->nullable();
            $table->integer('balid')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('remark')->nullable();
            $table->string('area')->nullable();
            $table->unique(['date', 'emp_code']);
        });
        DB::connection('device_log_fixture_setup')->table('all_att_log')->insert([
            ['id' => 1, 'date' => '2026-09-02 08:10:00', 'emp_code' => 'CARD-1', 'area' => 'Synthetic gate'],
            ['id' => 2, 'date' => '2026-09-02 17:30:00', 'emp_code' => 'CARD-1', 'area' => null],
            ['id' => 3, 'date' => '2026-09-02 12:00:00', 'emp_code' => 'CARD-1', 'area' => null],
            ['id' => 4, 'date' => '2026-09-02 09:00:00', 'emp_code' => 'CARD-9', 'area' => null],
            ['id' => 5, 'date' => '2026-09-02 09:05:00', 'emp_code' => 'CARD-2', 'area' => null],
            ['id' => 6, 'date' => '2026-09-02 25:61:00', 'emp_code' => 'CARD-1', 'area' => null],
            ['id' => 7, 'date' => '2026-09-02 10:00:00', 'emp_code' => ' ', 'area' => null],
            ['id' => 8, 'date' => '2026-09-03 18:00:00', 'emp_code' => 'CARD-1', 'area' => null],
            ['id' => 9, 'date' => '2026-09-05 08:00:00', 'emp_code' => 'CARD-1', 'area' => null],
        ]);

        $other = $this->company('Harbor Batch Other');
        $otherEmployee = $this->employee($other, 'SRC-OTHER');
        $this->identity($other, $otherEmployee, 'CARD-2');
        $own = $this->company('Harbor Batch Own');
        $first = $this->employee($own, 'SRC-1');
        $second = $this->employee($own, 'SRC-2');
        $this->identity($own, $first, 'CARD-1');
        $this->identity($own, $second, 'CARD-2');
        $this->signInAsWriter($own);
        $this->app->instance(CompanyContext::class, new CompanyContext($own->id));
        $ingested = app(DeviceEventIngestion::class)->ingest('all_att_log:2', 'CARD-1', '2026-09-02T17:30:00+06:00');
        $this->app->forgetInstance(CompanyContext::class);
        $this->assertSame(DeviceEventIngestion::RECORDED, $ingested['outcome'], (string) $ingested['message']);
        app(PunchRecordingRegistry::class)->record($own->id, ['employee_id' => $first->id, 'identity_value' => 'CARD-1', 'source_name' => 'synthetic device export', 'event_at' => '2026-09-02T12:00:00+06:00']);
        Carbon::setTestNow(Carbon::parse('2026-09-03T12:00:00+06:00'));

        return ['own' => $own, 'other' => $other];
    }

    private function company(string $name): Company
    {
        $company = Company::query()->create(['company_name' => $name, 'status' => 'Active']);
        AttendancePolicy::query()->create([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'default',
            'punch_selection' => 'first_and_last',
            'correction_fields' => '',
        ]);

        return $company;
    }

    private function employee(Company $company, string $code): Employee
    {
        return Employee::query()->create(['company_id' => $company->id, 'employee_code' => $code, 'display_name' => 'Synthetic '.$code]);
    }

    private function identity(Company $company, Employee $employee, string $card): void
    {
        PunchIdentity::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'identity_value' => $card,
            'effective_from' => Carbon::parse('2026-01-01T00:00:00Z'),
        ]);
    }

    private function signInAsWriter(Company $company): void
    {
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert(['user_id' => $user->id, 'company_id' => $company->id, 'authorized_at' => now()]);
    }
}
