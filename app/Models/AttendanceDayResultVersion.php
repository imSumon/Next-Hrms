<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDayResultVersion extends Model
{
    use HasCanonicalUlid;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'employee_id',
        'work_date',
        'shift_version_id',
        'status_code',
        'incomplete',
        'late_seconds',
        'early_seconds',
        'selected_in_event_id',
        'selected_out_event_id',
        'generation_kind',
        'correction_id',
        'previous_version_id',
        'roster_source_day_id',
        'override_in_at',
        'override_out_at',
        'override_in_request_id',
        'override_out_request_id',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'incomplete' => 'boolean',
            'late_seconds' => 'integer',
            'early_seconds' => 'integer',
            'override_in_at' => 'datetime',
            'override_out_at' => 'datetime',
            'calculated_at' => 'datetime',
        ];
    }

    public function selectedIn(): BelongsTo
    {
        return $this->belongsTo(PunchEvent::class, 'selected_in_event_id');
    }

    public function selectedOut(): BelongsTo
    {
        return $this->belongsTo(PunchEvent::class, 'selected_out_event_id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }
}
