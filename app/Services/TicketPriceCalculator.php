<?php

namespace App\Services;

use App\Models\PricingRule;
use App\Models\Seat;
use App\Models\Showtime;
use Illuminate\Support\Collection;

class TicketPriceCalculator
{
    public function forSeat(Showtime $showtime, Seat $seat): float
    {
        return $this->pricesForSeats($showtime, collect([$seat]))->get($seat->id);
    }

    public function pricesForSeats(Showtime $showtime, Collection $seats): Collection
    {
        $date = $showtime->starts_at;
        $dayType = $date->isWeekend() ? 'weekend' : 'weekday';
        $time = $date->format('H:i:s');
        $seatTypes = $seats->pluck('seat_type')->unique()->all();
        $rules = PricingRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($seatTypes) {
                $query->whereNull('seat_type')->orWhereIn('seat_type', $seatTypes);
            })
            ->where(function ($query) use ($showtime) {
                $query->whereNull('format_id')->orWhere('format_id', $showtime->format_id);
            })
            ->whereIn('day_type', ['any', $dayType])
            ->get();

        return $seats->mapWithKeys(function (Seat $seat) use ($rules, $time, $showtime) {
            $matchingRules = $rules
                ->filter(function (PricingRule $rule) use ($seat, $time) {
                    if ($rule->seat_type !== null && $rule->seat_type !== $seat->seat_type) {
                        return false;
                    }

                    if ($rule->time_start !== null) {
                        return $rule->time_start <= $time && $rule->time_end >= $time;
                    }

                    return true;
                })
                ->sort(function (PricingRule $left, PricingRule $right) {
                    return [
                        $right->priority,
                        $this->specificity($right),
                        -$right->id,
                    ] <=> [
                        $left->priority,
                        $this->specificity($left),
                        -$left->id,
                    ];
                });

            return [
                $seat->id => $this->calculate(
                    $showtime->basePriceForSeatType($seat->seat_type),
                    $matchingRules
                ),
            ];
        });
    }

    public function calculate(float $basePrice, Collection $rules): float
    {
        $rule = $rules->first();

        if (! $rule) {
            return round($basePrice, 2);
        }

        return round(
            $rule->amount_type === 'fixed'
                ? (float) $rule->amount
                : $basePrice + (float) $rule->amount,
            2
        );
    }

    private function specificity(PricingRule $rule): int
    {
        return (int) ($rule->seat_type !== null)
            + (int) ($rule->format_id !== null)
            + (int) ($rule->day_type !== 'any')
            + (int) ($rule->time_start !== null);
    }
}
