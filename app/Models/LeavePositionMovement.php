<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePositionMovement extends Model
{
    use HasCanonicalUlid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'leave_position_id',
        'amount',
        'direction',
        'related_reference',
        'reverses_movement_id',
        'acting_user_id',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(LeavePosition::class, 'leave_position_id');
    }

    public function reversedMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_movement_id');
    }

    public function actingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_user_id');
    }
}
