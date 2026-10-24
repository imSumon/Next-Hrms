<?php

namespace App\Leave;

use App\Models\LeavePositionWriter;

class LeavePositionWriterGrant
{
    public function allows(string $userId, string $companyId): bool
    {
        return LeavePositionWriter::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->exists();
    }
}
