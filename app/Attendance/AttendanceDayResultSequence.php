<?php

namespace App\Attendance;

use App\Models\AttendanceDayResultVersion;
use Illuminate\Support\Collection;

class AttendanceDayResultSequence
{
    /**
     * Order versions by the predecessor chain. A stored clock can place a
     * later version earlier than its predecessor, so calculated_at is not the order.
     *
     * @param  Collection<int, AttendanceDayResultVersion>  $versions
     * @return Collection<int, AttendanceDayResultVersion>
     */
    public function ordered(Collection $versions): Collection
    {
        /** @var array<string, list<AttendanceDayResultVersion>> $children */
        $children = [];
        foreach ($versions as $version) {
            $parent = $version->previous_version_id ?? '';
            $children[$parent][] = $version;
        }
        foreach ($children as &$group) {
            usort($group, fn (AttendanceDayResultVersion $left, AttendanceDayResultVersion $right): int => strcmp((string) $left->id, (string) $right->id));
        }
        unset($group);

        $ordered = [];
        $seen = [];
        $queue = $children[''] ?? [];
        while ($queue !== []) {
            $version = array_shift($queue);
            if (! $version instanceof AttendanceDayResultVersion || isset($seen[$version->id])) {
                continue;
            }
            $seen[$version->id] = true;
            $ordered[] = $version;
            foreach ($children[$version->id] ?? [] as $child) {
                $queue[] = $child;
            }
        }
        foreach ($versions->sortBy('id')->values() as $version) {
            if (! isset($seen[$version->id])) {
                $ordered[] = $version;
            }
        }

        return collect($ordered)->values();
    }
}
