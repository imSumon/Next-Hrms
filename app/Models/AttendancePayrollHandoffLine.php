<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;

class AttendancePayrollHandoffLine extends Model
{
    use HasCanonicalUlid;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'attendance_payroll_handoff_id',
        'employee_id',
        'work_date',
        'result_version_id',
        'day_class',
        'status_code',
        'incomplete',
        'late_seconds',
        'early_seconds',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'incomplete' => 'boolean',
            'late_seconds' => 'integer',
            'early_seconds' => 'integer',
        ];
    }
}
