<?php

namespace App\Attendance;

use App\Audit\EmployeeAuditTrail;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceCorrectionValue;
use App\Models\AttendanceDayResult;
use App\Models\AttendanceDayResultVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionRegistry
{
    public function __construct(private AttendanceDayProcessor $processor) {}

    public function submit(string $companyId, string $employeeId, string $workDate, string $requestedStatus, string $reason): AttendanceCorrection
    {
        $actorId = $this->actorId();
        $policy = $this->processor->policy($companyId);
        if (! $policy->allowsStatusCorrection()) {
            throw ValidationException::withMessages([
                'field_key' => 'This company has not allowed a status correction.',
            ]);
        }
        $requestedStatus = trim($requestedStatus);
        $reason = trim($reason);
        if ($requestedStatus === '' || $reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A correction needs a status and a reason.',
            ]);
        }
        app(AttendancePeriodRegistry::class)->assertWritable($companyId, $workDate);
        $pointer = $this->pointer($companyId, $employeeId, $workDate);
        $current = AttendanceDayResultVersion::query()->find($pointer->current_version_id);
        if (! $current instanceof AttendanceDayResultVersion) {
            throw ValidationException::withMessages([
                'work_date' => 'The current day result is missing.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $employeeId, $workDate, $requestedStatus, $reason, $actorId, $current): AttendanceCorrection {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $correction = AttendanceCorrection::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'work_date' => $workDate,
                'field_key' => 'status_code',
                'correction_state' => 'submitted',
                'reason' => $reason,
                'requested_by_user_id' => $actorId,
                'result_version_id' => $current->id,
                'requested_at' => now()->utc(),
            ]);
            $this->value($companyId, $correction->id, 'original', $current->status_code);
            $this->value($companyId, $correction->id, 'requested', $requestedStatus);
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_correction',
                (string) $correction->id,
                'attendance_correction_submitted',
                ['status_code' => $current->status_code],
                ['status_code' => $requestedStatus, 'employee_id' => $employeeId, 'work_date' => $workDate],
            );

            return $correction;
        });
    }

    public function decide(string $companyId, string $correctionId, string $decision): AttendanceCorrection
    {
        $actorId = $this->actorId();
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'The decision must approve or reject the correction.',
            ]);
        }
        $correction = AttendanceCorrection::query()
            ->where('company_id', $companyId)
            ->where('id', $correctionId)
            ->first();
        if (! $correction instanceof AttendanceCorrection || $correction->correction_state !== 'submitted') {
            throw ValidationException::withMessages([
                'correction_id' => 'There is no submitted correction to decide.',
            ]);
        }
        app(AttendancePeriodRegistry::class)->assertWritable($companyId, $correction->work_date->toDateString());

        return DB::transaction(function () use ($companyId, $correction, $decision, $actorId): AttendanceCorrection {
            DB::statement("SET LOCAL TIME ZONE 'UTC'");
            $pointer = $this->pointer($companyId, $correction->employee_id, $correction->work_date->toDateString());
            if ($pointer->current_version_id !== $correction->result_version_id) {
                throw ValidationException::withMessages([
                    'work_date' => 'The day result changed after this correction was submitted.',
                ]);
            }
            $correction->decided_by_user_id = $actorId;
            $correction->decided_at = now()->utc();
            if ($decision === 'rejected') {
                $correction->correction_state = 'rejected';
                $correction->save();
                app(EmployeeAuditTrail::class)->record(
                    $companyId,
                    'attendance_correction',
                    (string) $correction->id,
                    'attendance_correction_rejected',
                    ['correction_state' => 'submitted'],
                    ['correction_state' => 'rejected'],
                );

                return $correction;
            }

            $source = AttendanceDayResultVersion::query()->find($correction->result_version_id);
            $requested = AttendanceCorrectionValue::query()
                ->where('attendance_correction_id', $correction->id)
                ->where('value_stage', 'requested')
                ->first();
            if (! $source instanceof AttendanceDayResultVersion || ! $requested instanceof AttendanceCorrectionValue) {
                throw ValidationException::withMessages([
                    'correction_id' => 'The correction is missing its source result or requested value.',
                ]);
            }
            $this->value($companyId, $correction->id, 'approved', $requested->field_value);
            $version = AttendanceDayResultVersion::query()->create([
                'company_id' => $companyId,
                'employee_id' => $source->employee_id,
                'work_date' => $source->work_date->toDateString(),
                'shift_version_id' => $source->shift_version_id,
                'status_code' => $requested->field_value,
                'incomplete' => $source->incomplete,
                'late_seconds' => $source->late_seconds,
                'early_seconds' => $source->early_seconds,
                'selected_in_event_id' => $source->selected_in_event_id,
                'selected_out_event_id' => $source->selected_out_event_id,
                'generation_kind' => 'correction',
                'correction_id' => $correction->id,
                'previous_version_id' => $source->id,
                'roster_source_day_id' => $source->roster_source_day_id,
                'override_in_at' => $source->override_in_at,
                'override_out_at' => $source->override_out_at,
                'override_in_request_id' => $source->override_in_request_id,
                'override_out_request_id' => $source->override_out_request_id,
                'calculated_at' => now()->utc(),
            ]);
            $this->value($companyId, $correction->id, 'final', $version->status_code);
            $pointer->current_version_id = $version->id;
            $pointer->save();
            $correction->correction_state = 'applied';
            $correction->applied_version_id = $version->id;
            $correction->save();
            app(EmployeeAuditTrail::class)->record(
                $companyId,
                'attendance_correction',
                (string) $correction->id,
                'attendance_correction_applied',
                ['status_code' => $source->status_code, 'result_version_id' => $source->id],
                ['status_code' => $version->status_code, 'result_version_id' => $version->id],
            );

            return $correction;
        });
    }

    private function pointer(string $companyId, string $employeeId, string $workDate): AttendanceDayResult
    {
        $pointer = AttendanceDayResult::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $workDate)
            ->first();
        if (! $pointer instanceof AttendanceDayResult) {
            throw ValidationException::withMessages([
                'work_date' => 'Calculate the day before correcting it.',
            ]);
        }

        return $pointer;
    }

    private function value(string $companyId, string $correctionId, string $stage, ?string $fieldValue): void
    {
        AttendanceCorrectionValue::query()->create([
            'company_id' => $companyId,
            'attendance_correction_id' => $correctionId,
            'value_stage' => $stage,
            'field_key' => 'status_code',
            'field_value' => $fieldValue,
        ]);
    }

    private function actorId(): string
    {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw ValidationException::withMessages([
                'acting_user_id' => 'A correction requires the acting user.',
            ]);
        }

        return $actorId;
    }
}
