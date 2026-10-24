<?php

namespace App\Http\Controllers;

use App\Attendance\AttendanceCorrectionRegistry;
use App\Attendance\AttendanceDayProcessor;
use App\Attendance\AttendanceDayResultSequence;
use App\Attendance\AttendanceMissingPunchRegistry;
use App\Http\Requests\DecideAttendanceCorrectionRequest;
use App\Http\Requests\DecideMissingPunchRequest;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Http\Requests\StoreAttendanceDayCalculationRequest;
use App\Http\Requests\StoreMissingPunchRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendanceMissingPunchRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Tenancy\CompanyContext;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;

class AttendanceDayResultController extends Controller
{
    public function __construct(
        private AttendanceDayProcessor $processor,
        private AttendanceCorrectionRegistry $corrections,
    ) {}

    public function jsonIndex(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $results = AttendanceDayResult::query()
            ->where('company_id', $company->id)
            ->with(['employee', 'currentVersion'])
            ->orderBy('work_date')
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'company_name' => $company->company_name,
            'data' => $results->map(function (AttendanceDayResult $result): array {
                $version = $result->currentVersion;

                return [
                    'employee_id' => $result->employee_id,
                    'employee_code' => $result->employee?->employee_code,
                    'display_name' => $result->employee?->display_name,
                    'work_date' => $result->work_date?->toDateString(),
                    'current_version_id' => $result->current_version_id,
                    'version_count' => $this->versionCount($result),
                    'status_code' => $version?->status_code,
                    'incomplete' => $version?->incomplete,
                    'late_seconds' => $version?->late_seconds,
                    'early_seconds' => $version?->early_seconds,
                    'selected_in_event_id' => $version?->selected_in_event_id,
                    'selected_out_event_id' => $version?->selected_out_event_id,
                    'generation_kind' => $version?->generation_kind,
                ];
            })->values()->all(),
        ]);
    }

    public function jsonShow(CompanyContext $context, string $employee, string $workDate): JsonResponse
    {
        $company = $this->company($context);
        $this->assertEmployee($company->id, $employee);
        $result = AttendanceDayResult::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $employee)
            ->whereDate('work_date', $workDate)
            ->first();
        abort_if($result === null, 404);

        return response()->json($this->detail($company, $result));
    }

    public function jsonCalculate(StoreAttendanceDayCalculationRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $existing = AttendanceDayResult::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('work_date', $validated['work_date'])
            ->first();
        $previousVersionId = $existing?->current_version_id;
        $this->processor->calculate($company->id, $validated['employee_id'], $validated['work_date']);
        $result = AttendanceDayResult::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('work_date', $validated['work_date'])
            ->first();
        abort_if($result === null, 404);
        $detail = $this->detail($company, $result);
        $detail['version_added'] = $previousVersionId !== $result->current_version_id;

        return response()->json($detail, $detail['version_added'] ? 201 : 200);
    }

    public function jsonStoreCorrection(StoreAttendanceCorrectionRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $correction = $this->corrections->submit(
            $company->id,
            $validated['employee_id'],
            $validated['work_date'],
            $validated['status_code'],
            $validated['reason'],
        );

        return response()->json($this->correctionRow($correction), 201);
    }

    public function jsonDecideCorrection(DecideAttendanceCorrectionRequest $request, CompanyContext $context, string $correction): JsonResponse
    {
        $company = $this->company($context);
        $decided = $this->corrections->decide($company->id, $correction, $request->validated('decision'));

        return response()->json($this->correctionRow($decided));
    }

    public function jsonStoreMissingPunch(StoreMissingPunchRequest $request, CompanyContext $context, AttendanceMissingPunchRegistry $missingPunches): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $stored = $missingPunches->submit(
            $company->id,
            $validated['employee_id'],
            $validated['work_date'],
            $validated['punch_side'],
            $validated['requested_event_at'],
            $validated['reason'],
        );

        return response()->json($this->missingPunchRow($stored), 201);
    }

    public function jsonDecideMissingPunch(DecideMissingPunchRequest $request, CompanyContext $context, AttendanceMissingPunchRegistry $missingPunches, string $missingPunch): JsonResponse
    {
        $company = $this->company($context);
        $decided = $missingPunches->decide($company->id, $missingPunch, $request->validated('decision'));

        return response()->json($this->missingPunchRow($decided));
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function assertEmployee(string $companyId, string $employeeId): void
    {
        $exists = Employee::query()->where('company_id', $companyId)->where('id', $employeeId)->exists();
        abort_unless($exists, 404);
    }

    private function versionCount(AttendanceDayResult $result): int
    {
        return AttendanceDayResultVersion::query()
            ->where('company_id', $result->company_id)
            ->where('employee_id', $result->employee_id)
            ->whereDate('work_date', $result->work_date)
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Company $company, AttendanceDayResult $result): array
    {
        $versions = app(AttendanceDayResultSequence::class)->ordered(
            AttendanceDayResultVersion::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $result->employee_id)
                ->whereDate('work_date', $result->work_date)
                ->get(),
        );
        $corrections = AttendanceCorrection::query()
            ->where('company_id', $company->id)
            ->where('employee_id', $result->employee_id)
            ->whereDate('work_date', $result->work_date)
            ->orderBy('requested_at')
            ->orderBy('id')
            ->get();

        return [
            'company_name' => $company->company_name,
            'employee_id' => $result->employee_id,
            'work_date' => $result->work_date?->toDateString(),
            'current_version_id' => $result->current_version_id,
            'versions' => $versions->map(fn (AttendanceDayResultVersion $version): array => [
                'id' => $version->id,
                'previous_version_id' => $version->previous_version_id,
                'generation_kind' => $version->generation_kind,
                'status_code' => $version->status_code,
                'incomplete' => $version->incomplete,
                'late_seconds' => $version->late_seconds,
                'early_seconds' => $version->early_seconds,
                'shift_version_id' => $version->shift_version_id,
                'selected_in_event_id' => $version->selected_in_event_id,
                'selected_out_event_id' => $version->selected_out_event_id,
                'override_in_at' => $this->atom($version->override_in_at),
                'override_out_at' => $this->atom($version->override_out_at),
                'override_in_request_id' => $version->override_in_request_id,
                'override_out_request_id' => $version->override_out_request_id,
                'calculated_at' => $this->atom($version->calculated_at),
            ])->values()->all(),
            'corrections' => $corrections->map(fn (AttendanceCorrection $correction): array => $this->correctionRow($correction))->values()->all(),
            'missing_punch_requests' => AttendanceMissingPunchRequest::query()
                ->where('company_id', $company->id)
                ->where('employee_id', $result->employee_id)
                ->whereDate('work_date', $result->work_date)
                ->orderBy('requested_at')
                ->orderBy('id')
                ->get()
                ->map(fn (AttendanceMissingPunchRequest $request): array => $this->missingPunchRow($request))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function missingPunchRow(AttendanceMissingPunchRequest $request): array
    {
        return [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'work_date' => $request->work_date?->toDateString(),
            'punch_side' => $request->punch_side,
            'requested_event_at' => $this->atom($request->requested_event_at),
            'reason' => $request->reason,
            'request_state' => $request->request_state,
            'requested_by_user_id' => $request->requested_by_user_id,
            'requested_at' => $this->atom($request->requested_at),
            'decided_by_user_id' => $request->decided_by_user_id,
            'decided_at' => $this->atom($request->decided_at),
            'result_version_id' => $request->result_version_id,
            'applied_version_id' => $request->applied_version_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function correctionRow(AttendanceCorrection $correction): array
    {
        return [
            'id' => $correction->id,
            'employee_id' => $correction->employee_id,
            'work_date' => $correction->work_date?->toDateString(),
            'field_key' => $correction->field_key,
            'correction_state' => $correction->correction_state,
            'reason' => $correction->reason,
            'result_version_id' => $correction->result_version_id,
            'applied_version_id' => $correction->applied_version_id,
        ];
    }

    private function atom(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DateTimeInterface::ATOM) : null;
    }
}
