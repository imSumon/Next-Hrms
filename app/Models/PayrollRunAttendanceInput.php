<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;

class PayrollRunAttendanceInput extends Model
{
    use HasCanonicalUlid;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'input_key',
        'copied_value',
        'attendance_payroll_handoff_id',
    ];

    protected function casts(): array
    {
        return [
            'copied_value' => 'integer',
        ];
    }
}
