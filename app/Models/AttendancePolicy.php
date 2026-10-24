<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePolicy extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'timezone',
        'shift_winner',
        'punch_selection',
        'grace_seconds',
        'late_measured_from',
        'early_threshold_seconds',
        'present_status_code',
        'incomplete_status_code',
        'correction_fields',
        'working_day_code',
        'non_working_day_code',
        'non_working_status_code',
        'non_working_attended_status_code',
        'missing_punch_requests',
    ];

    protected function casts(): array
    {
        return [
            'grace_seconds' => 'integer',
            'early_threshold_seconds' => 'integer',
            'missing_punch_requests' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allowsStatusCorrection(): bool
    {
        return $this->correction_fields === 'status_code';
    }

    public function isNonWorkingStatus(?string $statusCode): bool
    {
        return $statusCode !== null
            && in_array($statusCode, array_filter([$this->non_working_status_code, $this->non_working_attended_status_code]), true);
    }
}
