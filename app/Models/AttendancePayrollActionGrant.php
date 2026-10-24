<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePayrollActionGrant extends Model
{
    public const FINALIZE_PERIOD = 'finalize_attendance_period';

    public const REOPEN_PERIOD = 'reopen_attendance_period';

    public const HANDOFF_PERIOD = 'handoff_attendance_period';

    public const REVIEW_MISSING_PUNCH = 'review_missing_punch';

    public const CONSUME_HANDOFF = 'consume_attendance_handoff';

    /**
     * @var list<string>
     */
    public const ACTIONS = [
        self::FINALIZE_PERIOD,
        self::REOPEN_PERIOD,
        self::HANDOFF_PERIOD,
        self::REVIEW_MISSING_PUNCH,
        self::CONSUME_HANDOFF,
    ];

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'action_key',
        'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
