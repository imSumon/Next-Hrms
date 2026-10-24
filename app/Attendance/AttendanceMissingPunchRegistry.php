<?php

namespace App\Attendance;

use App\Audit\EmployeeAuditTrail;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use App\Models\AttendanceMissingPunchRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceMissingPunchRegistry
{
    public function __construct(private AttendanceDayProcessor $processor) {}

    /**
     * Request a time for a side that has no recorded punch. The request is reviewed
     * before it counts; the raw punch store is never written.
     */
    public function submit(string $companyId, string $employeeId, string $workDate, string $side, string $requestedAt, string $reason): AttendanceMissingPunchRequest
    {
        $actorId = $this->actorId();
        $policy = $this->processor->policy($companyId);
        if (! $policy->missing_punch_requests) {
            throw ValidationException::withMessages([
                'punch_side' => 'This company has not allowed missing-punch requests.',
            ]);
        }
        if (! in_array($side, ['in', 'out'], true)) {
            throw ValidationException::withMessages(['punch_side' => 'The side must be in or out.']);
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A missing-punch request needs a reason.']);
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/', $requestedAt)) {
            throw ValidationException::withMessages([
                'requested_event_at' => 'The requested time needs a date, a time, and a UTC offset. A blank time is not filled from the shift.',
            ]);
        }
        $instant = Carbon::parse($requestedAt);
        app(AttendancePeriodRegistry::class)->assertWritable($companyId, $workDate);
        $current = $this->current($companyId, $employeeId, $workDate);
        if ($current->generation_kind === 'correction') {
            throw ValidationException::withMessages([
                'work_date' => 'This day has an approved status correction. A missing-punch time would not change it.',
            ]);
        }
        $recorded = $side === 'in' ? $current->selected_in_event_id : $current->selected_out_event_id;
        $overridden = $side === 'in' ? $current->override_in_request_id : $current->override_out_request_id;
        if ($recorded !== null || $overridden !== null) {
            throw ValidationException::withMessages([
                'punch_side' => 'This side already has a punch. Replacing a recorded punch time is not an approved workflow.',
            ]);
        }
        $window = $this->processor->window($companyId, $employeeId, $workDate);
        if ($instant->lessThan($window['from']) || $instant->greaterThan($window['to'])) {
            throw ValidationException::withMessages([
                'requested_event_at' => 'The requested time is outside the punch window for this day.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $employeeId, $workDate, $side, $instant, $reason, $actorId, $current): AttendanceMissingPunchRequest {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $open = AttendanceMissingPunchRequest::query()
                ->where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->whereDate('work_date', $workDate)
                ->where('punch_side', $side)
                ->where('request_state', 'submitted')
                ->exists();
            if ($open) {
                throw ValidationException::withMessages([
                    'punch_side' => 'A request for this side is already waiting for review.',
                ]);
            }
            $request = AttendanceMissingPunchRequest::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'work_date' => $workDate,
                'punch_side' => $side,
                'requested_event_at' => $instant->copy()->utc(),
                'reason' => $reason,
                'request_state' => 'submitted',
                'requested_by_user_id' => $actorId,
                'requested_at' => now()->utc(),
                'result_version_id' => $current->id,
            ]);
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_missing_punch_request',
                (string) $request->id,
                'attendance_missing_punch_submitted',
                null,
                ['employee_id' => $employeeId, 'work_date' => $workDate, 'punch_side' => $side, 'requested_event_at' => $instant->toIso8601String()],
            );

            return $request;
        });
    }

    public function decide(string $companyId, string $requestId, string $decision): AttendanceMissingPunchRequest
    {
        $actorId = $this->actorId();
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'The decision must approve or reject the request.']);
        }
        $requestedBy = AttendanceMissingPunchRequest::query()
            ->where('company_id', $companyId)
            ->where('id', $requestId)
            ->value('requested_by_user_id');
        if ($requestedBy === $actorId) {
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_missing_punch_request',
                $requestId,
                'attendance_missing_punch_self_review_refused',
                null,
                ['decision' => $decision, 'acting_user_id' => $actorId],
            );
            throw ValidationException::withMessages([
                'request_id' => 'The requester cannot review their own missing-punch request.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $requestId, $decision, $actorId): AttendanceMissingPunchRequest {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $request = AttendanceMissingPunchRequest::query()
                ->where('company_id', $companyId)
                ->where('id', $requestId)
                ->lockForUpdate()
                ->first();
            if (! $request instanceof AttendanceMissingPunchRequest || $request->request_state !== 'submitted') {
                throw ValidationException::withMessages([
                    'request_id' => 'There is no submitted missing-punch request to decide.',
                ]);
            }
            if ($request->requested_by_user_id === $actorId) {
                throw ValidationException::withMessages([
                    'request_id' => 'The requester cannot review their own missing-punch request.',
                ]);
            }
            $workDate = $request->work_date->toDateString();
            app(AttendancePeriodRegistry::class)->assertWritable($companyId, $workDate);
            $current = $this->current($companyId, $request->employee_id, $workDate);
            if ($current->id !== $request->result_version_id) {
                throw ValidationException::withMessages([
                    'work_date' => 'The day result changed after this request was submitted.',
                ]);
            }
            $request->decided_by_user_id = $actorId;
            $request->decided_at = now()->utc();
            $request->request_state = $decision === 'approved' ? 'applied' : 'rejected';
            $request->save();
            if ($decision === 'approved') {
                $version = $this->processor->calculateForMissingPunch($companyId, $request->employee_id, $workDate);
                $request->applied_version_id = $version->id;
                $request->save();
            }
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_missing_punch_request',
                (string) $request->id,
                $decision === 'approved' ? 'attendance_missing_punch_applied' : 'attendance_missing_punch_rejected',
                ['request_state' => 'submitted'],
                ['request_state' => $request->request_state, 'applied_version_id' => $request->applied_version_id],
            );

            return $request;
        });
    }

    private function current(string $companyId, string $employeeId, string $workDate): AttendanceDayResultVersion
    {
        $pointer = AttendanceDayResult::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $workDate)
            ->first();
        $current = $pointer instanceof AttendanceDayResult
            ? AttendanceDayResultVersion::query()->find($pointer->current_version_id)
            : null;
        if (! $current instanceof AttendanceDayResultVersion) {
            throw ValidationException::withMessages([
                'work_date' => 'Calculate the day before requesting a missing punch.',
            ]);
        }

        return $current;
    }

    private function actorId(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw ValidationException::withMessages([
                'acting_user_id' => 'A missing-punch request requires the acting user.',
            ]);
        }

        return $actorId;
    }
}
