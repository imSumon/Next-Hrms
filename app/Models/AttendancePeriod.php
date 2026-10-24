<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;

class AttendancePeriod extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'period_state',
        'finalized_by_user_id',
        'finalized_at',
        'reopened_by_user_id',
        'reopened_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'finalized_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }
}
