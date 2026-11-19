<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MockPaymentSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mock_payment_confirms_a_pending_booking_in_testing(): void
    {
        [$user, $booking] = $this->bookingFixture();

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking), ['payment_method' => 'mock_test'])
            ->assertRedirect(route('bookings.show', $booking));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('mock_test', $booking->payment_method);
        $this->assertSame(10, $user->fresh()->loyalty_points);
    }

    public function test_client_cannot_choose_payment_result_or_payment_status(): void
    {
        [$user, $booking] = $this->bookingFixture();

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking), [
                'payment_result' => 'success',
                'payment_status' => 'paid',
            ])
            ->assertSessionHasErrors(['payment_result', 'payment_status']);

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    public function test_mock_payment_is_rejected_in_production(): void
    {
        [$user, $booking] = $this->bookingFixture();
        $this->app->detectEnvironment(static fn () => 'production');

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking))
            ->assertForbidden();

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    public function test_client_cannot_supply_an_unapproved_payment_method(): void
    {
        [$user, $booking] = $this->bookingFixture();

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking), ['payment_method' => 'credit_card'])
            ->assertSessionHasErrors('payment_method');

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->payment_method);
    }

    public function test_cancelled_booking_cannot_be_paid(): void
    {
        [$user, $booking] = $this->bookingFixture();
        $booking->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
        ]);

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking))
            ->assertSessionHasErrors('payment');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('cancelled', $booking->fresh()->payment_status);
        $this->assertSame(0, $user->fresh()->loyalty_points);
    }

    public function test_expired_booking_is_cancelled_instead_of_paid(): void
    {
        [$user, $booking] = $this->bookingFixture();
        $booking->update(['hold_expires_at' => now()->subSecond()]);

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking))
            ->assertSessionHasErrors('payment');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('timeout', $booking->fresh()->payment_status);
        $this->assertSame(0, $user->fresh()->loyalty_points);
    }

    public function test_repeated_payment_request_does_not_duplicate_points_or_reference(): void
    {
        [$user, $booking] = $this->bookingFixture();
        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking), ['payment_method' => 'mock_test'])
            ->assertRedirect(route('bookings.show', $booking));

        $booking->refresh();
        $paymentReference = $booking->payment_reference;

        $this->post(route('bookings.payment.process', $booking))
            ->assertRedirect(route('bookings.show', $booking));

        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame($paymentReference, $booking->fresh()->payment_reference);
        $this->assertSame(10, $user->fresh()->loyalty_points);
    }

    public function test_previously_paid_booking_does_not_earn_points_again(): void
    {
        [$user, $booking] = $this->bookingFixture();
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'mock_test',
        ]);
        $user->update(['loyalty_points' => 40]);

        $this->actingAs($user)
            ->post(route('bookings.payment.process', $booking))
            ->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(40, $user->fresh()->loyalty_points);
    }

    private function bookingFixture(): array
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true])->save();
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Mock payment cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Mock payment room',
            'format_id' => $format->id,
            'total_seats' => 1,
            'is_active' => true,
        ]);
        $movie = Movie::create([
            'title' => 'Mock payment movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
            'format_id' => $format->id,
        ]);
        $startsAt = Carbon::now()->addDay();
        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'price' => 80000,
            'status' => 'scheduled',
        ]);
        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => 'YF-'.Str::upper(Str::random(12)),
            'total_amount' => 80000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'hold_expires_at' => now()->addMinutes(5),
        ]);

        return [$user, $booking];
    }
}
