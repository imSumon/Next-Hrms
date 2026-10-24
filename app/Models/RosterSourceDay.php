<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterSourceDay extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'employee_id',
        'shift_id',
        'work_date',
        'source_day_code',
        'always_present',
        'source_roster_flag',
        'window_starts_at',
        'window_ends_at',
        'shift_starts_at',
        'shift_ends_at',
        'absent_after_at',
        'source_system',
        'source_key',
        'source_catalog_key',
        'source_employee_key',
        'source_shift_key',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'always_present' => 'boolean',
            'window_starts_at' => 'datetime',
            'window_ends_at' => 'datetime',
            'shift_starts_at' => 'datetime',
            'shift_ends_at' => 'datetime',
            'absent_after_at' => 'datetime',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
