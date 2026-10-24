<?php

namespace App\Punch;

use App\Models\PunchRecordingWriter;

class PunchRecordingWriterGrant
{
    public function allows(string $userId, string $companyId): bool
    {
        return PunchRecordingWriter::query()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->exists();
    }
}
