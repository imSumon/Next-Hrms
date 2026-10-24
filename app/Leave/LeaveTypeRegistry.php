<?php

namespace App\Leave;

use App\Audit\EmployeeAuditTrail;
use App\Models\AuditEvent;
use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveTypeRegistry
{
    /**
     * @return Collection<int, LeaveType>
     */
    public function list(string $companyId): Collection
    {
        return LeaveType::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function find(string $companyId, string $typeId): ?LeaveType
    {
        return LeaveType::query()
            ->where('company_id', $companyId)
            ->where('id', $typeId)
            ->first();
    }

    public function create(string $companyId, string $name, string $status): LeaveType
    {
        return LeaveType::query()->create([
            'company_id' => $companyId,
            'name' => $name,
            'status' => $status,
        ]);
    }

    public function update(LeaveType $type, string $name, string $status): LeaveType
    {
        $previousName = $type->name;
        $previousStatus = $type->status;

        return DB::transaction(function () use ($type, $previousName, $previousStatus, $name, $status) {
            $type->name = $name;
            $type->status = $status;
            $type->save();

            if ($previousName !== $name) {
                app(EmployeeAuditTrail::class)->record(
                    (string) $type->company_id,
                    AuditEvent::SUBJECT_LEAVE_TYPE,
                    (string) $type->id,
                    AuditEvent::ACTION_NAME_UPDATED,
                    ['name' => $previousName],
                    ['name' => $name],
                );
            }

            if ($previousStatus !== $status) {
                app(EmployeeAuditTrail::class)->record(
                    (string) $type->company_id,
                    AuditEvent::SUBJECT_LEAVE_TYPE,
                    (string) $type->id,
                    AuditEvent::ACTION_STATUS_UPDATED,
                    ['status' => $previousStatus],
                    ['status' => $status],
                );
            }

            return $type;
        });
    }
}
