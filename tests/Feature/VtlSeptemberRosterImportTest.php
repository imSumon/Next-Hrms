<?php

namespace Tests\Feature;

use App\Attendance\AttendanceDayProcessor;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendancePayrollActionGrant;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\Employee;
use App\Models\PunchEvent;
use App\Models\PunchRecordingWriter;
use App\Models\RosterSourceDay;
use App\Models\ShiftAssignmentCandidate;
use App\Models\User;
use App\Tenancy\CompanyContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\EmployeeDatabaseTestCase;

class VtlSeptemberRosterImportTest extends EmployeeDatabaseTestCase
{
    private const VTL_COMPANY_ID = '01M4KG2384JEKYAE5SXC927QDF';

    public function test_the_ninety_calendar_rows_reconcile_to_the_selected_employees_dates_and_shifts(): void
    {
        $this->artisan('hrms:import-vtl-september-roster')
            ->expectsOutput('inserted_source_days=0')
            ->expectsOutput('inserted_roster_candidates=0')
            ->assertSuccessful();

        $extract = require database_path('data/vtl_september_2026_roster.php');
        $stored = RosterSourceDay::query()
            ->where('company_id', self::VTL_COMPANY_ID)
            ->where('source_system', 'legacy_vtlhrm')
            ->get();
        $this->assertCount(90, $extract);
        $this->assertCount(90, $stored);
        $this->assertEqualsCanonicalizing(array_map(fn (array $row): string => (string) $row['id'], $extract), $stored->pluck('source_key')->all());
        $expectedShift = ['03123173' => '14', '03129487' => '14', '05000356' => '16'];
        foreach ($expectedShift as $code => $shiftKey) {
            $employee = $this->vtlEmployee($code);
            $days = $stored->where('employee_id', $employee->id);
            $this->assertCount(30, $days, $code);
            $this->assertSame(range(1, 30), $days->map(fn (RosterSourceDay $day): int => (int) $day->work_date->format('j'))->sort()->values()->all(), $code);
            $this->assertSame([$shiftKey], $days->pluck('source_shift_key')->unique()->values()->all(), $code);
            $this->assertSame([$code], $days->pluck('source_employee_key')->unique()->values()->all(), $code);
            $this->assertSame(['20'], $days->pluck('source_catalog_key')->unique()->values()->all(), $code);
            $this->assertSame(26, $days->where('source_day_code', 'W')->count(), $code);
            $holidays = $days->where('source_day_code', 'H')->map(fn (RosterSourceDay $day): string => $day->work_date->toDateString())->sort()->values()->all();
            $this->assertSame(['2026-09-04', '2026-09-11', '2026-09-18', '2026-09-25'], $holidays, $code);
            foreach ($days as $day) {
                $this->assertSame($shiftKey, $day->shift?->source_key);
            }
        }
        $this->assertSame(87, ShiftAssignmentCandidate::query()->where('company_id', self::VTL_COMPANY_ID)->where('candidate_kind', 'roster')->where('source_system', 'legacy_vtlhrm')->count());
        $synthetic = ShiftAssignmentCandidate::query()->where('company_id', self::VTL_COMPANY_ID)->where('candidate_kind', 'roster')->where('source_system', 'synthetic_attendance_test')->get();
        $this->assertCount(4, $synthetic);
        foreach ($synthetic as $candidate) {
            $source = $stored->first(fn (RosterSourceDay $day): bool => $day->employee_id === $candidate->employee_id
                && $day->work_date->toDateString() === Carbon::parse($candidate->roster_work_date)->toDateString());
            if ($source instanceof RosterSourceDay) {
                $this->assertSame($source->shift_id, $candidate->shift_id);
            }
        }
        $policy = AttendancePolicy::query()->where('company_id', self::VTL_COMPANY_ID)->first();
        $this->assertSame('margin_first_and_last', $policy?->punch_selection);
        $this->assertSame(3, AttendanceDayResult::query()->where('company_id', self::VTL_COMPANY_ID)->whereHas('employee', fn ($query) => $query->where('source_system', 'legacy_vtlhrm'))->count());
        $this->assertSame('', $policy?->correction_fields);
    }

    public function test_the_stored_calendar_keeps_the_source_margins_for_a_next_day_two_shift(): void
    {
        $day = RosterSourceDay::query()
            ->where('company_id', self::VTL_COMPANY_ID)
            ->where('source_system', 'legacy_vtlhrm')
            ->where('source_key', '3023112')
            ->first();

        $this->assertInstanceOf(RosterSourceDay::class, $day);
        $this->assertSame('2026-09-02', $day->work_date->toDateString());
        $local = fn (Carbon $value): string => $value->copy()->timezone('Asia/Dhaka')->format('Y-m-d H:i:s');
        $this->assertSame('2026-09-02 07:30:00', $local($day->window_starts_at));
        $this->assertSame('2026-09-02 09:00:59', $local($day->shift_starts_at));
        $this->assertSame('2026-09-02 10:00:00', $local($day->absent_after_at));
        $this->assertSame('2026-09-02 17:30:00', $local($day->shift_ends_at));
        $this->assertSame('2026-09-03 05:55:00', $local($day->window_ends_at));
    }

    public function test_history_lists_the_original_vtl_version_first_although_its_reprocess_clock_is_earlier(): void
    {
        $this->signInAsVtlWriter();
        $employee = $this->vtlEmployee('03123173');
        $versions = AttendanceDayResultVersion::query()->where('employee_id', $employee->id)->whereDate('work_date', '2026-09-02')->get();
        $original = $versions->firstWhere('previous_version_id', null);
        $reprocess = $versions->firstWhere('previous_version_id', $original?->id);
        $this->assertInstanceOf(AttendanceDayResultVersion::class, $reprocess);
        $this->assertTrue($reprocess->calculated_at->lessThan($original->calculated_at));

        $history = $this->getJson('/api/attendance/day-results/'.$employee->id.'/2026-09-02')->assertOk();

        $this->assertSame($original->id, $history->json('versions.0.id'));
        $this->assertSame($reprocess->id, $history->json('versions.1.id'));
        $this->assertSame($reprocess->id, $history->json('current_version_id'));
    }

    public function test_switching_vtl_to_the_calendar_window_rule_changes_only_what_the_evidence_supports(): void
    {
        $this->signInAsVtlWriter();
        AttendancePolicy::query()->where('company_id', self::VTL_COMPANY_ID)->update([
            'punch_selection' => 'margin_first_and_last',
            'working_day_code' => 'W',
            'non_working_day_code' => 'H',
            'non_working_status_code' => 'H',
            'non_working_attended_status_code' => 'HP',
        ]);
        $processor = app(AttendanceDayProcessor::class);
        $anayet = $this->vtlEmployee('03123173');
        $ragib = $this->vtlEmployee('03129487');
        $sabuj = $this->vtlEmployee('05000356');
        $before = $this->currentVersion($anayet, '2026-09-02');

        $audited = $processor->calculate(self::VTL_COMPANY_ID, $anayet->id, '2026-09-02');
        $this->assertSame('P', $audited->status_code);
        $this->assertSame(0, $audited->late_seconds);
        $this->assertSame($before->selected_in_event_id, $audited->selected_in_event_id);
        $this->assertSame($before->selected_out_event_id, $audited->selected_out_event_id);
        $this->assertSame($before->id, $audited->previous_version_id);
        $this->assertNotNull($audited->roster_source_day_id);

        $syntheticLate = $processor->calculate(self::VTL_COMPANY_ID, $ragib->id, '2026-09-03');
        $this->assertSame('P', $syntheticLate->status_code);
        $this->assertSame(600, $syntheticLate->late_seconds);

        $onePunch = $processor->calculate(self::VTL_COMPANY_ID, $sabuj->id, '2026-09-13');
        $this->assertSame('A', $onePunch->status_code);
        $this->assertTrue($onePunch->incomplete);

        $this->assertSame('H', $processor->calculate(self::VTL_COMPANY_ID, $anayet->id, '2026-09-04')->status_code);
        $this->assertSame('A', $processor->calculate(self::VTL_COMPANY_ID, $anayet->id, '2026-09-01')->status_code);

        $night = Employee::query()->where('company_id', self::VTL_COMPANY_ID)->where('employee_code', 'SYN-VTL-NIGHT')->first();
        $this->assertInstanceOf(Employee::class, $night);
        try {
            $processor->calculate(self::VTL_COMPANY_ID, $night->id, '2026-09-04');
            $this->fail('A synthetic roster row without a calendar window must not be calculated.');
        } catch (ValidationException $exception) {
            $this->assertSame('This date has no roster day window.', $exception->errors()['work_date'][0]);
        }
    }

    public function test_the_legacy_out_application_explains_the_single_punch_present_day(): void
    {
        $this->signInAsVtlWriter();
        AttendancePolicy::query()->where('company_id', self::VTL_COMPANY_ID)->update([
            'punch_selection' => 'margin_first_and_last',
            'working_day_code' => 'W',
            'non_working_day_code' => 'H',
            'non_working_status_code' => 'H',
            'non_working_attended_status_code' => 'HP',
            'missing_punch_requests' => true,
        ]);
        $sabuj = $this->vtlEmployee('05000356');
        $processor = app(AttendanceDayProcessor::class);
        $this->assertSame('A', $processor->calculate(self::VTL_COMPANY_ID, $sabuj->id, '2026-09-13')->status_code);
        $reviewer = User::query()->create(['name' => 'Synthetic VTL Reviewer', 'email' => 'vtl-reviewer-'.strtolower((string) Str::ulid()).'@example.test', 'password' => 'synthetic-passphrase-1']);
        CompanyMembership::query()->create(['user_id' => $reviewer->id, 'company_id' => self::VTL_COMPANY_ID, 'status' => 'active', 'can_manage_employees' => false]);
        AttendancePayrollActionGrant::query()->insert(['user_id' => $reviewer->id, 'company_id' => self::VTL_COMPANY_ID, 'action_key' => AttendancePayrollActionGrant::REVIEW_MISSING_PUNCH, 'authorized_at' => now()]);

        $request = $this->postJson('/api/attendance/missing-punch-requests', [
            'employee_id' => $sabuj->id,
            'work_date' => '2026-09-13',
            'punch_side' => 'out',
            'requested_event_at' => '2026-09-13T17:00:00+06:00',
            'reason' => 'Synthetic replay of legacy approval 77696, whose blank out time became the shift end',
        ])->assertCreated();
        $this->actingAs($reviewer);
        session([CompanyContext::SESSION_KEY => self::VTL_COMPANY_ID]);
        $this->postJson('/api/attendance/missing-punch-requests/'.$request->json('id').'/decision', ['decision' => 'approved'])->assertOk();

        $version = $this->currentVersion($sabuj, '2026-09-13');
        $this->assertSame('P', $version->status_code);
        $this->assertNull($version->selected_out_event_id);
        $this->assertSame(1, PunchEvent::query()->where('company_id', self::VTL_COMPANY_ID)->whereHas('identity', fn ($query) => $query->where('employee_id', $sabuj->id))->count());
    }

    private function signInAsVtlWriter(): void
    {
        $company = Company::query()->find(self::VTL_COMPANY_ID);
        $this->assertInstanceOf(Company::class, $company);
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'authorized_at' => now(),
        ]);
    }

    private function vtlEmployee(string $code): Employee
    {
        $employee = Employee::query()
            ->where('company_id', self::VTL_COMPANY_ID)
            ->where('source_system', 'legacy_vtlhrm')
            ->where('employee_code', $code)
            ->first();
        $this->assertInstanceOf(Employee::class, $employee);

        return $employee;
    }

    private function currentVersion(Employee $employee, string $workDate): AttendanceDayResultVersion
    {
        $pointer = AttendanceDayResult::query()
            ->where('company_id', self::VTL_COMPANY_ID)
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)
            ->first();
        $version = $pointer instanceof AttendanceDayResult ? AttendanceDayResultVersion::query()->find($pointer->current_version_id) : null;
        $this->assertInstanceOf(AttendanceDayResultVersion::class, $version);

        return $version;
    }
}
