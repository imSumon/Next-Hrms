<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PunchEvent extends Model
{
    use HasCanonicalUlid;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'punch_identity_id',
        'source_name',
        'event_at',
        'ingested_at',
        'source_event_id',
        'acting_user_id',
        'withdrawal_status',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'ingested_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(PunchIdentity::class, 'punch_identity_id');
    }

    public function actingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_user_id');
    }
}
