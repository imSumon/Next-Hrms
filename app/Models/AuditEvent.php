<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class AuditEvent extends Model
{
    use HasCanonicalUlid;

    public const UPDATED_AT = null;

    public const SUBJECT_EMPLOYEE = 'employee';

    public const SUBJECT_EPISODE = 'employment_episode';

    public const ACTION_DISPLAY_NAME_UPDATED = 'display_name_updated';

    public const ACTION_EPISODE_CREATED = 'episode_created';

    public const ACTION_EPISODE_UPDATED = 'episode_updated';

    public const SUBJECT_DEPARTMENT = 'department';

    public const SUBJECT_DESIGNATION = 'designation';

    public const ACTION_NAME_UPDATED = 'name_updated';

    public const ACTION_STATUS_UPDATED = 'status_updated';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'subject_kind',
        'subject_id',
        'action_kind',
        'acting_user_id',
        'acted_at',
        'previous_value',
        'new_value',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
            'previous_value' => 'array',
            'new_value' => 'array',
        ];
    }

    public function actingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acting_user_id');
    }

    public function actionLabel(): string
    {
        return match ($this->action_kind) {
            self::ACTION_DISPLAY_NAME_UPDATED => 'Display name updated',
            self::ACTION_EPISODE_CREATED => 'Employment episode created',
            self::ACTION_EPISODE_UPDATED => 'Employment episode updated',
            default => $this->action_kind,
        };
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return list<string>
     */
    public function valueLines(?array $values): array
    {
        if ($values === null) {
            return [];
        }

        $labels = [
            'display_name' => 'Display name',
            'joining_date' => 'Joining date',
            'last_working_date' => 'Last working date',
            'episode_status' => 'Status',
        ];
        $lines = [];

        foreach ($labels as $key => $label) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];
            $lines[] = $label.': '.($value === null || $value === '' ? '—' : $value);
        }

        return $lines;
    }

    /**
     * @return Collection<int, self>
     */
    public static function forEmployee(Employee $employee): Collection
    {
        $episodeIds = $employee->episodes->pluck('id');

        return self::query()
            ->where('company_id', $employee->company_id)
            ->where(function ($query) use ($employee, $episodeIds) {
                $query->where(function ($employeeEvents) use ($employee) {
                    $employeeEvents->where('subject_kind', self::SUBJECT_EMPLOYEE)
                        ->where('subject_id', $employee->id);
                })->orWhere(function ($episodeEvents) use ($episodeIds) {
                    $episodeEvents->where('subject_kind', self::SUBJECT_EPISODE)
                        ->whereIn('subject_id', $episodeIds);
                });
            })
            ->with('actingUser')
            ->orderBy('acted_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, self>
     */
    public static function displayNameChangesFor(string $companyId, string $employeeId): Collection
    {
        return self::query()
            ->where('company_id', $companyId)
            ->where('subject_kind', self::SUBJECT_EMPLOYEE)
            ->where('subject_id', $employeeId)
            ->where('action_kind', self::ACTION_DISPLAY_NAME_UPDATED)
            ->with('actingUser')
            ->orderBy('acted_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, self>
     */
    public static function masterChangesFor(string $companyId, string $subjectKind, string $subjectId): Collection
    {
        return self::query()
            ->where('company_id', $companyId)
            ->where('subject_kind', $subjectKind)
            ->where('subject_id', $subjectId)
            ->whereIn('action_kind', [self::ACTION_NAME_UPDATED, self::ACTION_STATUS_UPDATED])
            ->with('actingUser')
            ->orderBy('acted_at')
            ->orderBy('id')
            ->get();
    }
}
