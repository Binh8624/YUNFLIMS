<?php

namespace App\Services;

use App\Models\Seat;

class SingleSeatRule
{
    public function leavesSingleSeat(iterable $seats, iterable $occupiedSeatIds, iterable $selectedSeatIds): bool
    {
        return $this->orphanedSeatIds($seats, $occupiedSeatIds, $selectedSeatIds) !== [];
    }

    public function orphanedSeatIds(iterable $seats, iterable $occupiedSeatIds, iterable $selectedSeatIds): array
    {
        $occupied = array_fill_keys(array_map('intval', is_array($occupiedSeatIds) ? $occupiedSeatIds : iterator_to_array($occupiedSeatIds)), true);
        $selected = array_fill_keys(array_map('intval', is_array($selectedSeatIds) ? $selectedSeatIds : iterator_to_array($selectedSeatIds)), true);
        $orphanedSeatIds = [];

        foreach (collect($seats)->groupBy('row_number') as $rowSeats) {
            $orderedSeats = $rowSeats
                ->sortBy(fn (Seat $seat) => [$seat->seat_number, $seat->id])
                ->values();
            $segment = [];
            $previousPosition = null;

            foreach ($orderedSeats as $seat) {
                if (! $seat->is_active) {
                    $orphanedSeatIds = [...$orphanedSeatIds, ...$this->segmentOrphanedSeatIds($segment, $occupied, $selected)];

                    $segment = [];
                    $previousPosition = null;

                    continue;
                }

                if ($previousPosition !== null && $seat->seat_number !== $previousPosition + 1) {
                    $orphanedSeatIds = [...$orphanedSeatIds, ...$this->segmentOrphanedSeatIds($segment, $occupied, $selected)];

                    $segment = [];
                }

                $segment[] = $seat;
                $previousPosition = $seat->seat_number;
            }

            $orphanedSeatIds = [...$orphanedSeatIds, ...$this->segmentOrphanedSeatIds($segment, $occupied, $selected)];
        }

        return array_values(array_unique($orphanedSeatIds));
    }

    private function segmentOrphanedSeatIds(array $segment, array $occupied, array $selected): array
    {
        $position = 0;
        $count = count($segment);
        $orphanedSeatIds = [];

        while ($position < $count) {
            $seat = $segment[$position];
            if (isset($occupied[$seat->id]) || isset($selected[$seat->id])) {
                $position++;

                continue;
            }

            $runStart = $position;
            while (
                $position < $count
                && ! isset($occupied[$segment[$position]->id])
                && ! isset($selected[$segment[$position]->id])
            ) {
                $position++;
            }

            if (
                $position - $runStart === 1
                && (
                    ($runStart > 0 && $this->isOccupied($segment[$runStart - 1], $occupied, $selected))
                    || ($position < $count && $this->isOccupied($segment[$position], $occupied, $selected))
                )
            ) {
                $orphanedSeatIds[] = $segment[$runStart]->id;
            }
        }

        return $orphanedSeatIds;
    }

    private function isOccupied(Seat $seat, array $occupied, array $selected): bool
    {
        return isset($occupied[$seat->id]) || isset($selected[$seat->id]);
    }
}
