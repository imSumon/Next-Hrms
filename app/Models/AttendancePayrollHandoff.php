<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendancePayrollHandoff extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'attendance_period_id',
        'handoff_state',
        'exported_by_user_id',
        'exported_at',
        'employee_count',
        'line_count',
        'present_count',
        'incomplete_count',
        'non_working_count',
        'other_status_count',
        'late_seconds_total',
    ];

    protected function casts(): array
    {
        return [
            'exported_at' => 'datetime',
            'employee_count' => 'integer',
            'line_count' => 'integer',
            'present_count' => 'integer',
            'incomplete_count' => 'integer',
            'non_working_count' => 'integer',
            'other_status_count' => 'integer',
            'late_seconds_total' => 'integer',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AttendancePayrollHandoffLine::class);
    }
}
