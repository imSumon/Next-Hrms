<?php

namespace App\Audit;

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Auth;

class EmployeeAuditTrail
{
    /**
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>|null  $new
     */
    public function record(
        string $companyId,
        string $subjectKind,
        string $subjectId,
        string $actionKind,
        ?array $previous,
        ?array $new,
        ?string $reason = null,
    ): AuditEvent {
        $actorId = Auth::id();
        if (! is_string($actorId) || $actorId === '') {
            throw new \RuntimeException('An audit event requires the acting user.');
        }

        return AuditEvent::query()->create([
            'company_id' => $companyId,
            'subject_kind' => $subjectKind,
            'subject_id' => $subjectId,
            'action_kind' => $actionKind,
            'acting_user_id' => $actorId,
            'acted_at' => now(),
            'previous_value' => $previous,
            'new_value' => $new,
            'reason' => $reason,
        ]);
    }
}
