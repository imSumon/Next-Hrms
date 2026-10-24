<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollPeriodRequest;
use App\Http\Requests\StorePayrollRunRequest;
use App\Models\AttendancePayrollHandoff;
use App\Models\Company;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\PayrollRunAttendanceInput;
use App\Models\PayrollRunDeclaredInput;
use App\Payroll\PayrollAttendanceIntake;
use App\Tenancy\CompanyContext;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollIntakeController extends Controller
{
    public function __construct(private PayrollAttendanceIntake $intake) {}

    public function jsonIndex(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $periods = PayrollPeriod::query()->where('company_id', $company->id)->orderByDesc('period_start')->get();
        $runs = PayrollRun::query()->where('company_id', $company->id)->orderBy('run_sequence')->get()->groupBy('payroll_period_id');
        $handoffs = AttendancePayrollHandoff::query()
            ->where('company_id', $company->id)
            ->where('handoff_state', 'exported')
            ->orderByDesc('exported_at')
            ->get();

        return response()->json([
            'company_name' => $company->company_name,
            'input_keys' => PayrollAttendanceIntake::ATTENDANCE_INPUTS,
            'data' => $periods->map(fn (PayrollPeriod $period): array => [
                'id' => $period->id,
                'period_start' => $period->period_start->toDateString(),
                'period_end' => $period->period_end->toDateString(),
                'label' => $period->label,
                'runs' => ($runs->get($period->id) ?? collect())->map(fn (PayrollRun $run): array => $this->runRow($run))->values()->all(),
            ])->values()->all(),
            'exported_handoffs' => $handoffs->map(fn (AttendancePayrollHandoff $handoff): array => [
                'id' => $handoff->id,
                'attendance_period_id' => $handoff->attendance_period_id,
                'employee_count' => $handoff->employee_count,
                'line_count' => $handoff->line_count,
            ])->values()->all(),
        ]);
    }

    public function jsonStorePeriod(StorePayrollPeriodRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $period = $this->intake->openPeriod($company->id, $request->validated('period_start'), $request->validated('period_end'), $request->validated('label'));

        return response()->json([
            'id' => $period->id,
            'period_start' => $period->period_start->toDateString(),
            'period_end' => $period->period_end->toDateString(),
            'label' => $period->label,
        ], 201);
    }

    public function jsonStoreRun(StorePayrollRunRequest $request, CompanyContext $context, string $payrollPeriod): JsonResponse
    {
        $company = $this->company($context);
        $run = $this->intake->startRun($company->id, $payrollPeriod, array_values($request->validated('input_keys')));

        return response()->json($this->runDetail($run), 201);
    }

    public function jsonTakeAttendance(Request $request, CompanyContext $context, string $payrollRun): JsonResponse
    {
        $validated = $request->validate([
            'attendance_payroll_handoff_id' => ['required', 'string', 'regex:/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/'],
        ]);
        $company = $this->company($context);
        $taken = $this->intake->takeAttendance($company->id, $payrollRun, $validated['attendance_payroll_handoff_id']);

        return response()->json($this->runDetail($taken['run']) + ['created' => $taken['created']], $taken['created'] ? 201 : 200);
    }

    public function jsonShowRun(CompanyContext $context, string $payrollRun): JsonResponse
    {
        $company = $this->company($context);
        $run = PayrollRun::query()->where('company_id', $company->id)->where('id', $payrollRun)->first();
        abort_if($run === null, 404);

        return response()->json($this->runDetail($run));
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    /**
     * @return array<string, mixed>
     */
    private function runRow(PayrollRun $run): array
    {
        return [
            'id' => $run->id,
            'payroll_period_id' => $run->payroll_period_id,
            'run_sequence' => $run->run_sequence,
            'run_state' => $run->run_state,
            'attendance_usage' => $run->attendance_usage,
            'attendance_payroll_handoff_id' => $run->attendance_payroll_handoff_id,
            'attendance_taken_at' => $run->attendance_taken_at instanceof DateTimeInterface ? $run->attendance_taken_at->format(DateTimeInterface::ATOM) : null,
            'previous_run_id' => $run->previous_run_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runDetail(PayrollRun $run): array
    {
        return $this->runRow($run) + [
            'declared_inputs' => PayrollRunDeclaredInput::query()->where('payroll_run_id', $run->id)->orderBy('input_key')->pluck('input_key')->all(),
            'attendance_inputs' => PayrollRunAttendanceInput::query()
                ->where('company_id', $run->company_id)
                ->where('payroll_run_id', $run->id)
                ->orderBy('employee_id')
                ->orderBy('input_key')
                ->get()
                ->map(fn (PayrollRunAttendanceInput $input): array => [
                    'employee_id' => $input->employee_id,
                    'input_key' => $input->input_key,
                    'copied_value' => $input->copied_value,
                    'attendance_payroll_handoff_id' => $input->attendance_payroll_handoff_id,
                ])
                ->values()
                ->all(),
        ];
    }
}
