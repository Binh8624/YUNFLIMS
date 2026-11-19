<?php

namespace App\Services;

use Carbon\CarbonInterface;

class ShowtimeRules
{
    public static function supportsFormat(iterable $movieFormatIds, iterable $roomFormatIds, int $formatId): bool
    {
        return in_array($formatId, is_array($movieFormatIds) ? $movieFormatIds : iterator_to_array($movieFormatIds), false)
            && in_array($formatId, is_array($roomFormatIds) ? $roomFormatIds : iterator_to_array($roomFormatIds), false);
    }

    public static function overlaps(
        CarbonInterface $start,
        CarbonInterface $end,
        CarbonInterface $existingStart,
        CarbonInterface $existingEnd,
        int $cleanupMinutes = 15
    ): bool {
        $blockedEnd = $end->copy()->addMinutes($cleanupMinutes);
        $blockedExistingEnd = $existingEnd->copy()->addMinutes($cleanupMinutes);

        return $start->lt($blockedExistingEnd) && $blockedEnd->gt($existingStart);
    }
}
