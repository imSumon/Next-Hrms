<?php

namespace App\Http\Controllers;

use App\Attendance\AttendancePeriodRegistry;
use App\Attendance\AttendancePeriodReport;
use App\Http\Requests\AttendanceReportRequest;
use App\Http\Requests\ReopenAttendancePeriodRequest;
use App\Http\Requests\StoreAttendancePeriodRequest;
use App\Models\AttendancePayrollHandoff;
use App\Models\AttendancePayrollHandoffLine;
use App\Models\AttendancePeriod;
use App\Models\AttendancePeriodEvent;
use App\Models\Company;
use App\Tenancy\CompanyContext;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;

class AttendancePeriodController extends Controller
{
    public function __construct(
        private AttendancePeriodRegistry $periods,
        private AttendancePeriodReport $report,
    ) {}

    public function jsonReport(AttendanceReportRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);

        return response()->json(['company_name' => $company->company_name] + $this->report->build($company->id, $request->validated('from'), $request->validated('to')));
    }

    public function jsonIndex(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $periods = AttendancePeriod::query()
            ->where('company_id', $company->id)
            ->orderByDesc('period_start')
            ->get();

        return response()->json([
            'company_name' => $company->company_name,
            'data' => $periods->map(fn (AttendancePeriod $period): array => $this->periodRow($period))->values()->all(),
        ]);
    }

    public function jsonStore(StoreAttendancePeriodRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $period = $this->periods->open($company->id, $request->validated('period_start'), $request->validated('period_end'));

        return response()->json($this->detail($company, $period), 201);
    }

    public function jsonShow(CompanyContext $context, string $period): JsonResponse
    {
        $company = $this->company($context);

        return response()->json($this->detail($company, $this->find($company->id, $period)));
    }

    public function jsonFinalize(CompanyContext $context, string $period): JsonResponse
    {
        $company = $this->company($context);
        $finalized = $this->periods->finalize($company->id, $this->find($company->id, $period)->id);

        return response()->json($this->detail($company, $finalized));
    }

    public function jsonReopen(ReopenAttendancePeriodRequest $request, CompanyContext $context, string $period): JsonResponse
    {
        $company = $this->company($context);
        $reopened = $this->periods->reopen($company->id, $this->find($company->id, $period)->id, $request->validated('reason'));

        return response()->json($this->detail($company, $reopened));
    }

    public function jsonExport(CompanyContext $context, string $period): JsonResponse
    {
        $company = $this->company($context);
        $exported = $this->periods->export($company->id, $this->find($company->id, $period)->id);

        return response()->json($this->handoff($exported['handoff']) + ['created' => $exported['created']], $exported['created'] ? 201 : 200);
    }

    public function jsonShowHandoff(CompanyContext $context, string $handoff): JsonResponse
    {
        $company = $this->company($context);
        $found = AttendancePayrollHandoff::query()
            ->where('company_id', $company->id)
            ->where('id', $handoff)
            ->first();
        abort_if($found === null, 404);

        return response()->json($this->handoff($found));
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function find(string $companyId, string $periodId): AttendancePeriod
    {
        $period = AttendancePeriod::query()
            ->where('company_id', $companyId)
            ->where('id', $periodId)
            ->first();
        abort_if($period === null, 404);

        return $period;
    }

    /**
     * @return array<string, mixed>
     */
    private function periodRow(AttendancePeriod $period): array
    {
        return [
            'id' => $period->id,
            'period_start' => $period->period_start->toDateString(),
            'period_end' => $period->period_end->toDateString(),
            'period_state' => $period->period_state,
            'finalized_at' => $this->atom($period->finalized_at),
            'reopened_at' => $this->atom($period->reopened_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Company $company, AttendancePeriod $period): array
    {
        $events = AttendancePeriodEvent::query()
            ->where('company_id', $company->id)
            ->where('attendance_period_id', $period->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
        $handoffs = AttendancePayrollHandoff::query()
            ->where('company_id', $company->id)
            ->where('attendance_period_id', $period->id)
            ->orderBy('exported_at')
            ->orderBy('id')
            ->get();

        return [
            'company_name' => $company->company_name,
            'period' => $this->periodRow($period),
            'events' => $events->map(fn (AttendancePeriodEvent $event): array => [
                'event_kind' => $event->event_kind,
                'acting_user_id' => $event->acting_user_id,
                'reason' => $event->reason,
                'occurred_at' => $this->atom($event->occurred_at),
            ])->values()->all(),
            'handoffs' => $handoffs->map(fn (AttendancePayrollHandoff $handoff): array => $this->handoffHeader($handoff))->values()->all(),
            'report' => $this->report->build($company->id, $period->period_start->toDateString(), $period->period_end->toDateString()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handoffHeader(AttendancePayrollHandoff $handoff): array
    {
        return [
            'id' => $handoff->id,
            'attendance_period_id' => $handoff->attendance_period_id,
            'handoff_state' => $handoff->handoff_state,
            'exported_by_user_id' => $handoff->exported_by_user_id,
            'exported_at' => $this->atom($handoff->exported_at),
            'employee_count' => $handoff->employee_count,
            'line_count' => $handoff->line_count,
            'present_count' => $handoff->present_count,
            'incomplete_count' => $handoff->incomplete_count,
            'non_working_count' => $handoff->non_working_count,
            'other_status_count' => $handoff->other_status_count,
            'late_seconds_total' => $handoff->late_seconds_total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handoff(AttendancePayrollHandoff $handoff): array
    {
        $lines = AttendancePayrollHandoffLine::query()
            ->where('company_id', $handoff->company_id)
            ->where('attendance_payroll_handoff_id', $handoff->id)
            ->orderBy('work_date')
            ->orderBy('employee_id')
            ->get();

        return $this->handoffHeader($handoff) + [
            'lines' => $lines->map(fn (AttendancePayrollHandoffLine $line): array => [
                'employee_id' => $line->employee_id,
                'work_date' => $line->work_date->toDateString(),
                'result_version_id' => $line->result_version_id,
                'day_class' => $line->day_class,
                'status_code' => $line->status_code,
                'incomplete' => $line->incomplete,
                'late_seconds' => $line->late_seconds,
                'early_seconds' => $line->early_seconds,
            ])->values()->all(),
        ];
    }

    private function atom(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DateTimeInterface::ATOM) : null;
    }
}
