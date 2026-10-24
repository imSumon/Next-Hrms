<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'payroll_period_id',
        'run_sequence',
        'run_state',
        'attendance_usage',
        'attendance_payroll_handoff_id',
        'attendance_taken_at',
        'created_by_user_id',
        'calculated_by_user_id',
        'calculated_at',
        'previous_run_id',
    ];

    protected function casts(): array
    {
        return [
            'run_sequence' => 'integer',
            'attendance_taken_at' => 'datetime',
            'calculated_at' => 'datetime',
        ];
    }

    public function declaredInputs(): HasMany
    {
        return $this->hasMany(PayrollRunDeclaredInput::class);
    }

    public function attendanceInputs(): HasMany
    {
        return $this->hasMany(PayrollRunAttendanceInput::class);
    }
}
