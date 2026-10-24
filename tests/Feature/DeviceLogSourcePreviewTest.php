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
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\EmployeeDatabaseTestCase;

class DeviceLogSourcePreviewTest extends EmployeeDatabaseTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_preview_classifies_each_source_row_and_writes_nothing(): void
    {
        $setup = $this->scenario();
        $punchesBefore = PunchEvent::query()->whereIn('company_id', [$setup['own']->id, $setup['other']->id])->count();
        $identitiesBefore = PunchIdentity::query()->whereIn('company_id', [$setup['own']->id, $setup['other']->id])->count();
        $sourceBefore = DB::connection('device_log_fixture')->table('all_att_log')->orderBy('id')->get()->toArray();

        $first = $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-03')->assertOk();
        $second = $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-03')->assertOk();

        $outcomes = collect($first->json('rows'))->pluck('outcome', 'source_event_id')->all();
        $this->assertSame([
            'all_att_log:1' => 'importable',
            'all_att_log:4' => 'unmapped',
            'all_att_log:5' => 'ambiguous',
            'all_att_log:7' => 'invalid_identity',
            'all_att_log:3' => 'possible_duplicate',
            'all_att_log:2' => 'already_recorded',
            'all_att_log:6' => 'invalid_timestamp',
            'all_att_log:8' => 'invalid_timestamp',
        ], $outcomes);
        $this->assertSame(['importable' => 1, 'already_recorded' => 1, 'possible_duplicate' => 1, 'unmapped' => 1, 'ambiguous' => 1, 'invalid_timestamp' => 2, 'invalid_identity' => 1, 'invalid_source_event' => 0], $first->json('counts'));
        foreach (array_keys($outcomes) as $sourceEventId) {
            $this->assertMatchesRegularExpression('/^all_att_log:[1-9]\d*$/', $sourceEventId);
        }
        $this->assertSame('2026-09-02T02:10:00+00:00', $first->json('rows.0.event_at'));
        $this->assertSame('SRC-1', $first->json('rows.0.employee_code'));
        $this->assertSame('This card is also registered in another company. The shared log has no company column, so the row cannot be attributed until only one company holds the card at that time.', $first->json('rows.2.message'));
        $this->assertFalse($first->json('writes_enabled'));
        $this->assertNotEmpty($first->json('blocking_decisions'));
        $this->assertSame($first->json('rows'), $second->json('rows'));

        $this->assertSame($punchesBefore, PunchEvent::query()->whereIn('company_id', [$setup['own']->id, $setup['other']->id])->count());
        $this->assertSame($identitiesBefore, PunchIdentity::query()->whereIn('company_id', [$setup['own']->id, $setup['other']->id])->count());
        $this->assertEquals($sourceBefore, DB::connection('device_log_fixture')->table('all_att_log')->orderBy('id')->get()->toArray());
        $this->expectException(QueryException::class);
        DB::connection('device_log_fixture')->table('all_att_log')->insert(['id' => 99, 'date' => '2026-09-02 11:00:00', 'emp_code' => 'CARD-1']);
    }

    public function test_preview_is_scoped_to_the_active_company(): void
    {
        $setup = $this->scenario();
        $this->signInAsWriter($setup['other']);

        $response = $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->assertOk();

        $rows = collect($response->json('rows'))->keyBy('source_event_id');
        $this->assertSame('unmapped', $rows['all_att_log:1']['outcome']);
        $this->assertNull($rows['all_att_log:1']['employee_id']);
        $this->assertSame('ambiguous', $rows['all_att_log:5']['outcome']);
        $this->assertSame('unmapped', $rows['all_att_log:2']['outcome']);
        $this->assertSame($setup['other']->company_name, $response->json('company_name'));
    }

    public function test_preview_needs_a_session_and_the_writer_grant(): void
    {
        $this->scenario();
        auth()->logout();
        $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->assertUnauthorized();

        $company = Company::query()->create(['company_name' => 'Harbor Device No Grant', 'status' => 'Active']);
        $this->signInFor($company, false);
        $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->assertForbidden();
    }

    public function test_preview_generates_canonical_source_event_ids_and_rejects_malformed_row_ids(): void
    {
        $this->scenario();
        DB::connection('device_log_fixture')->table('all_att_log')->insert([
            ['id' => 0, 'date' => '2026-09-02 11:11:00', 'emp_code' => 'CARD-1', 'area' => null],
        ]);

        $response = $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')->assertOk();
        $rows = collect($response->json('rows'))->keyBy('source_event_id');

        $this->assertSame('importable', $rows['all_att_log:1']['outcome']);
        $this->assertSame('invalid_source_event', $rows['all_att_log:0']['outcome']);
        $this->assertNotNull($rows['all_att_log:0']['message']);
        foreach ($rows->keys() as $sourceEventId) {
            if ($sourceEventId === 'all_att_log:0') {
                continue;
            }
            $this->assertMatchesRegularExpression('/^all_att_log:[1-9]\d*$/', (string) $sourceEventId);
        }
    }

    public function test_an_unconfigured_source_reports_its_state_without_reading(): void
    {
        config(['attendance.device_log.connection' => null]);
        $company = Company::query()->create(['company_name' => 'Harbor Device Unconfigured', 'status' => 'Active']);
        $this->signInAsWriter($company);

        $this->getJson('/api/attendance/device-log/preview?from=2026-09-02&to=2026-09-02')
            ->assertOk()
            ->assertJsonPath('source.configured', false)
            ->assertJsonPath('source.table', 'all_att_log')
            ->assertJsonPath('writes_enabled', false)
            ->assertJsonMissingPath('rows');
    }

    /**
     * Synthetic rows shaped like the legacy all_att_log table: id, naive wall-clock
     * date, card number in emp_code, and an uninterpreted area label.
     *
     * @return array{own: Company, other: Company}
     */
    private function scenario(): array
    {
        config([
            'database.connections.device_log_fixture' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false],
            'attendance.device_log.connection' => 'device_log_fixture',
        ]);
        DB::purge('device_log_fixture');
        Schema::connection('device_log_fixture')->create('all_att_log', function ($table): void {
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
        DB::connection('device_log_fixture')->table('all_att_log')->insert([
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

        $other = $this->company('Harbor Device Other');
        $otherEmployee = $this->employee($other, 'SRC-OTHER');
        $this->identity($other, $otherEmployee, 'CARD-2');
        $own = $this->company('Harbor Device Own');
        $first = $this->employee($own, 'SRC-1');
        $second = $this->employee($own, 'SRC-2');
        $this->identity($own, $first, 'CARD-1');
        $this->identity($own, $second, 'CARD-2');
        $this->signInAsWriter($own);
        $this->app->instance(CompanyContext::class, new CompanyContext($own->id));
        $ingested = app(DeviceEventIngestion::class)->ingest('all_att_log:2', 'CARD-1', '2026-09-02T17:30:00+06:00');
        $this->app->forgetInstance(CompanyContext::class);
        $this->assertSame(DeviceEventIngestion::RECORDED, $ingested['outcome'], (string) $ingested['message']);
        $this->assertSame($first->id, $ingested['employee_id']);
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
