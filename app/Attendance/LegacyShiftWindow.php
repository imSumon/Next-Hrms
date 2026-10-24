<?php

namespace App\Attendance;

/**
 * Documents the legacy VTL `next_day` values from `attshift/_form.php` and
 * `EmpshiftcalendarController`. This class does not select punches.
 *
 * 0 means the shift end and the out margin stay on the work date.
 * 1 means the shift end and the out margin move to the next calendar date.
 * 2 means the shift end stays on the work date and the out margin
 * (`punch_end_time`) moves to the next calendar date.
 *
 * `punch_start_time`, `punch_end_time`, and `absent_after` are not loaded.
 * They are not the day-selection rule used by {@see AttendanceDayProcessor}.
 */
final class LegacyShiftWindow
{
    public const SAME_DATE = 0;

    public const NEXT_DATE = 1;

    public const OUT_MARGIN_NEXT_DATE = 2;

    public static function crossesMidnight(int $nextDay): bool
    {
        return $nextDay === self::NEXT_DATE;
    }

    public static function punchMarginSupported(): bool
    {
        return false;
    }
}
