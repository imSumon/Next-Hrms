<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;

class AttendanceMissingPunchRequest extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'employee_id',
        'work_date',
        'punch_side',
        'requested_event_at',
        'reason',
        'request_state',
        'requested_by_user_id',
        'requested_at',
        'decided_by_user_id',
        'decided_at',
        'result_version_id',
        'applied_version_id',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'requested_event_at' => 'datetime',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }
}
