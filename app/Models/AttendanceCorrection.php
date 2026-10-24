<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceCorrection extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'employee_id',
        'work_date',
        'field_key',
        'correction_state',
        'reason',
        'requested_by_user_id',
        'decided_by_user_id',
        'result_version_id',
        'applied_version_id',
        'requested_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionValue::class);
    }
}
