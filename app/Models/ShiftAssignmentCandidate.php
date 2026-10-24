<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignmentCandidate extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'employee_id',
        'shift_id',
        'candidate_kind',
        'roster_work_date',
        'source_system',
        'source_key',
    ];

    protected function casts(): array
    {
        return [
            'roster_work_date' => 'date',
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
