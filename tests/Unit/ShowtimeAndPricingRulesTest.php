<?php

namespace Tests\Unit;

use App\Models\PricingRule;
use App\Models\Booking;
use App\Models\Showtime;
use App\Services\ShowtimeRules;
use App\Services\TicketPriceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ShowtimeAndPricingRulesTest extends TestCase
{
    public function test_format_must_be_supported_by_both_movie_and_room(): void
    {
        $this->assertTrue(ShowtimeRules::supportsFormat([1, 2], [2, 3], 2));
        $this->assertFalse(ShowtimeRules::supportsFormat([1], [1, 2], 2));
        $this->assertFalse(ShowtimeRules::supportsFormat([1, 2], [1], 2));
    }

    public function test_showtime_overlap_includes_cleanup_after_each_screening(): void
    {
        $start = Carbon::parse('2026-10-30 12:00');
        $end = Carbon::parse('2026-10-30 14:00');

        $this->assertTrue(ShowtimeRules::overlaps(
            $start,
            $end,
            Carbon::parse('2026-10-30 14:10'),
            Carbon::parse('2026-10-30 16:00')
        ));
        $this->assertFalse(ShowtimeRules::overlaps(
            $start,
            $end,
            Carbon::parse('2026-10-30 14:16'),
            Carbon::parse('2026-10-30 16:00')
        ));
    }

    public function test_ticket_price_uses_one_fixed_or_surcharge_rule(): void
    {
        $calculator = new TicketPriceCalculator;
        $rules = new Collection([
            new PricingRule(['amount_type' => 'surcharge', 'amount' => 12000]),
        ]);

        $this->assertSame(92000.0, $calculator->calculate(80000, $rules));

        $fixedRule = new PricingRule(['amount_type' => 'fixed', 'amount' => 100000]);
        $this->assertSame(100000.0, $calculator->calculate(80000, new Collection([$fixedRule])));
        $this->assertSame(80000.0, $calculator->calculate(80000, new Collection));
    }

    public function test_showtime_uses_a_base_price_for_each_seat_type_and_falls_back_for_legacy_prices(): void
    {
        $showtime = new Showtime([
            'price' => 80000,
            'vip_price' => 100000,
            'couple_price' => 160000,
        ]);

        $this->assertSame(80000.0, $showtime->basePriceForSeatType('standard'));
        $this->assertSame(100000.0, $showtime->basePriceForSeatType('vip'));
        $this->assertSame(160000.0, $showtime->basePriceForSeatType('couple'));

        $legacyShowtime = new Showtime(['price' => 80000]);
        $this->assertSame(80000.0, $legacyShowtime->basePriceForSeatType('vip'));
        $this->assertSame(80000.0, $legacyShowtime->basePriceForSeatType('couple'));
    }

    public function test_booking_qr_contains_a_real_verification_url(): void
    {
        $svg = Booking::generateQrSvg('YF-TEST-CODE');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<path', $svg);
        $this->assertStringContainsString('/tickets/YF-TEST-CODE/verify', route('tickets.verify', 'YF-TEST-CODE'));
    }
}
