<?php

namespace Tests\Feature;

use App\Models\AttendancePeriod;
use App\Models\AttendancePolicy;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PunchRecordingWriter;
use App\Models\Shift;
use App\Models\ShiftAssignmentCandidate;
use App\Models\ShiftVersion;
use App\Models\User;
use App\Punch\PunchRecordingRegistry;
use Tests\EmployeeDatabaseTestCase;

class AttendanceDashboardTest extends EmployeeDatabaseTestCase
{
    public function test_the_day_separates_missing_results_from_calculated_absence(): void
    {
        $setup = $this->processedDay('Harbor Dashboard Day');

        $response = $this->getJson('/api/attendance/dashboard?date=2026-09-02')->assertOk();

        $this->assertSame('Harbor Dashboard Day', $response->json('company_name'));
        $this->assertTrue($response->json('policy_configured'));
        $this->assertSame([
            'expected_employee_days' => 5,
            'employees_with_punches' => 3,
            'raw_punch_events' => 5,
            'results_calculated' => 3,
            'expected_without_result' => 2,
            'no_punch_received' => 1,
            'awaiting_processing' => 1,
            'present' => 1,
            'calculated_absent' => 1,
            'incomplete' => 1,
            'non_working' => 0,
            'other_status' => 0,
            'late' => 1,
            'late_not_calculated' => 1,
            'early_departure' => 0,
            'early_not_calculated' => 3,
            'approved_corrections' => 0,
            'approved_missing_punch_times' => 0,
        ], $response->json('day'));
        $this->assertSame('calendar_month', $response->json('queue.basis'));
        $this->assertSame('2026-09-01', $response->json('queue.from'));
        $this->assertSame('2026-09-02', $response->json('queue.to'));
        $this->assertSame(1, $response->json('queue.pending_corrections'));
        $this->assertNull($response->json('period'));
        $this->assertSame('not_configured', $response->json('source.status'));
        $this->assertSame([['source_name' => 'synthetic attendance test', 'events' => 5]], array_map(
            fn (array $row): array => ['source_name' => $row['source_name'], 'events' => $row['events']],
            $response->json('ingestion'),
        ));
        $this->assertSame($setup['company']->id, Employee::query()->find($setup['employees']['DSH-5']->id)?->company_id);
    }

    public function test_an_open_period_reports_its_finalization_blockers(): void
    {
        $setup = $this->processedDay('Harbor Dashboard Period');
        $this->postJson('/api/attendance/periods', ['period_start' => '2026-09-02', 'period_end' => '2026-09-02'])->assertCreated();

        $response = $this->getJson('/api/attendance/dashboard?date=2026-09-02')->assertOk();

        $this->assertSame('attendance_period', $response->json('queue.basis'));
        $this->assertSame('open', $response->json('period.period_state'));
        $this->assertContains('2 expected employee-days have no result.', $response->json('period.finalization_blockers'));
        $this->assertContains('1 corrections are still waiting for a decision.', $response->json('period.finalization_blockers'));
        $this->assertContains('1 employee-days have punches but no result.', $response->json('period.finalization_blockers'));
        $this->assertSame($setup['company']->id, AttendancePeriod::query()->where('company_id', $setup['company']->id)->value('company_id'));
    }

    public function test_the_dashboard_stays_inside_the_company_and_needs_the_writer_grant(): void
    {
        $this->processedDay('Harbor Dashboard Own');
        $other = Company::query()->create(['company_name' => 'Harbor Dashboard Other', 'status' => 'Active']);
        $this->signInAsWriter($other);

        $empty = $this->getJson('/api/attendance/dashboard?date=2026-09-02')->assertOk();
        $this->assertSame('Harbor Dashboard Other', $empty->json('company_name'));
        $this->assertFalse($empty->json('policy_configured'));
        $this->assertSame([], $empty->json('ingestion'));

        $ungranted = Company::query()->create(['company_name' => 'Harbor Dashboard Ungranted', 'status' => 'Active']);
        $this->signInFor($ungranted, false);
        $this->getJson('/api/attendance/dashboard')->assertForbidden();

        auth()->logout();
        $this->getJson('/api/attendance/dashboard')->assertUnauthorized();
    }

    public function test_the_attendance_entry_appears_in_both_menus_for_an_authorized_user(): void
    {
        $company = Company::query()->create(['company_name' => 'Harbor Dashboard Menu', 'status' => 'Active']);
        $this->signInAsWriter($company);
        $this->get('/employees')->assertOk()->assertSee('href="/session/attendance/dashboard"', false);

        $plain = Company::query()->create(['company_name' => 'Harbor Dashboard Menu Plain', 'status' => 'Active']);
        $this->signInFor($plain, false);
        $this->get('/employees')->assertOk()->assertDontSee('href="/session/attendance/dashboard"', false);

        $app = (string) file_get_contents(base_path('frontend/src/App.tsx'));
        $links = (string) file_get_contents(base_path('frontend/src/components/RealSessionLinks.tsx'));
        $page = (string) file_get_contents(base_path('frontend/src/pages/RealSessionAttendanceDashboardPage.tsx'));
        $this->assertStringContainsString('path="/session/attendance/dashboard"', $app);
        $this->assertStringContainsString('path="/session/attendance"', $app);
        $this->assertStringContainsString('path="/session/attendance/periods"', $app);
        $this->assertStringContainsString('to="/session/attendance/dashboard"', $links);
        $this->assertStringContainsString('No punch received', $page);
        $this->assertStringContainsString('not counted as absent', $page);
        $this->assertStringContainsString('<SourcePanel key={dashboard.work_date', $page);
        $this->assertStringContainsString("'/api/attendance/dashboard", (string) file_get_contents(base_path('frontend/src/lib/attendanceWorkflowApi.ts')));
    }

    /**
     * Five synthetic employees on 2026-09-02, shift 09:00–17:00, grace 0:
     * DSH-1 late and complete, DSH-2 one punch, DSH-3 no punch, all processed;
     * DSH-4 punched but not processed; DSH-5 no punch and no result.
     *
     * @return array{company: Company, user: User, employees: array<string, Employee>}
     */
    private function processedDay(string $name): array
    {
        $company = Company::query()->create(['company_name' => $name, 'status' => 'Active']);
        $user = $this->signInAsWriter($company);
        AttendancePolicy::query()->create([
            'company_id' => $company->id,
            'timezone' => 'Asia/Dhaka',
            'shift_winner' => 'default',
            'punch_selection' => 'first_and_last',
            'grace_seconds' => 0,
            'late_measured_from' => 'shift_start',
            'present_status_code' => 'P',
            'incomplete_status_code' => 'A',
            'correction_fields' => 'status_code',
        ]);
        $shift = Shift::query()->create(['company_id' => $company->id, 'name' => 'Day', 'source_system' => 'synthetic_attendance_test']);
        ShiftVersion::query()->create([
            'company_id' => $company->id,
            'shift_id' => $shift->id,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'crosses_midnight' => false,
            'effective_from' => '2026-01-01',
            'source_system' => 'synthetic_attendance_test',
        ]);
        $employees = [];
        foreach (['DSH-1', 'DSH-2', 'DSH-3', 'DSH-4', 'DSH-5'] as $code) {
            $employees[$code] = Employee::query()->create(['company_id' => $company->id, 'employee_code' => $code, 'display_name' => 'Synthetic '.$code]);
            ShiftAssignmentCandidate::query()->create([
                'company_id' => $company->id,
                'employee_id' => $employees[$code]->id,
                'shift_id' => $shift->id,
                'candidate_kind' => 'default',
                'source_system' => 'synthetic_attendance_test',
            ]);
        }
        $punches = app(PunchRecordingRegistry::class);
        foreach ([['DSH-1', '09:10:00'], ['DSH-1', '17:00:00'], ['DSH-2', '09:00:00'], ['DSH-4', '08:55:00'], ['DSH-4', '17:05:00']] as [$code, $time]) {
            $punches->record($company->id, ['employee_id' => $employees[$code]->id, 'identity_value' => $code, 'source_name' => 'synthetic attendance test', 'event_at' => '2026-09-02T'.$time.'+06:00']);
        }
        foreach (['DSH-1', 'DSH-2', 'DSH-3'] as $code) {
            $this->postJson('/api/attendance/day-results', ['employee_id' => $employees[$code]->id, 'work_date' => '2026-09-02'])->assertCreated();
        }
        $this->postJson('/api/attendance/corrections', ['employee_id' => $employees['DSH-1']->id, 'work_date' => '2026-09-02', 'status_code' => 'SL', 'reason' => 'Synthetic pending request'])->assertCreated();

        return ['company' => $company, 'user' => $user, 'employees' => $employees];
    }

    private function signInAsWriter(Company $company): User
    {
        $user = $this->signInFor($company, false);
        PunchRecordingWriter::query()->insert(['user_id' => $user->id, 'company_id' => $company->id, 'authorized_at' => now()]);

        return $user;
    }
}
