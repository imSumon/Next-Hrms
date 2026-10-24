<?php

namespace App\Attendance;

use App\Models\AttendancePayrollActionGrant;
use App\Models\CompanyMembership;

class AttendancePayrollActionGrantCheck
{
    public function allows(string $userId, string $companyId, string $action): bool
    {
        $member = CompanyMembership::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();

        return $member && AttendancePayrollActionGrant::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('action_key', $action)
            ->exists();
    }
}
