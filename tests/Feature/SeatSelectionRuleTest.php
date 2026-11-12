<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeatSelectionRuleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_availability_endpoint_reports_the_orphan_seat_and_does_not_ignore_other_bookings(): void
    {
        [$user, $showtime, $seats] = $this->fixture([1, 2, 3]);
        $this->booking($user, $showtime, $seats[1], 'confirmed');

        $this->actingAs($this->activeUser())
            ->postJson(route('bookings.seats.check', $showtime), ['seats' => [$seats[3]->id]])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('isolatedSeatIds.0', $seats[2]->id);
    }

    public function test_direct_booking_request_cannot_bypass_the_single_seat_rule(): void
    {
        [$user, $showtime, $seats] = $this->fixture([1, 2, 3]);
        $this->booking($user, $showtime, $seats[1], 'confirmed');
        $customer = $this->activeUser();

        $this->actingAs($customer)
            ->post(route('bookings.store', $showtime), ['seats' => [$seats[3]->id]])
            ->assertSessionHasErrors('seats');

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $customer->id,
            'showtime_id' => $showtime->id,
        ]);
        $this->assertDatabaseMissing('booking_seats', [
            'showtime_id' => $showtime->id,
            'seat_id' => $seats[3]->id,
        ]);
    }

    public function test_an_active_hold_blocks_a_seat_but_an_expired_hold_does_not(): void
    {
        [$user, $showtime, $seats] = $this->fixture([1]);
        $hold = $this->booking($user, $showtime, $seats[1], 'pending', now()->addMinutes(5));
        $customer = $this->activeUser();

        $this->actingAs($customer)
            ->postJson(route('bookings.seats.check', $showtime), ['seats' => [$seats[1]->id]])
            ->assertOk()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('unavailableSeats.'.$seats[1]->id, 'held');

        $hold->update(['hold_expires_at' => now()->subSecond()]);

        $this->postJson(route('bookings.seats.check', $showtime), ['seats' => [$seats[1]->id]])
            ->assertOk()
            ->assertJsonPath('valid', true);
    }

    public function test_two_customers_cannot_hold_the_same_seat_for_one_showtime(): void
    {
        [$firstCustomer, $showtime, $seats] = $this->fixture([1]);
        $secondCustomer = $this->activeUser();

        $this->actingAs($firstCustomer)
            ->post(route('bookings.store', $showtime), ['seats' => [$seats[1]->id]])
            ->assertRedirect();

        $this->actingAs($secondCustomer)
            ->post(route('bookings.store', $showtime), ['seats' => [$seats[1]->id]])
            ->assertSessionHasErrors('seats');

        $this->assertSame(
            1,
            BookingSeat::where('showtime_id', $showtime->id)->where('seat_id', $seats[1]->id)->count()
        );
    }

    public function test_another_showtime_in_the_same_room_has_an_independent_seat_map(): void
    {
        [$user, $showtime, $seats] = $this->fixture([1]);
        $this->booking($user, $showtime, $seats[1], 'confirmed');
        $otherShowtime = $this->showtime($showtime->movie, $showtime->room, $showtime->format);

        $this->actingAs($this->activeUser())
            ->postJson(route('bookings.seats.check', $otherShowtime), ['seats' => [$seats[1]->id]])
            ->assertOk()
            ->assertJsonPath('valid', true);
    }

    private function fixture(array $positions): array
    {
        $user = $this->activeUser();
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Seat test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Seat test room',
            'format_id' => $format->id,
            'total_seats' => count($positions),
            'is_active' => true,
        ]);
        $movie = Movie::create([
            'title' => 'Seat test movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
            'format_id' => $format->id,
        ]);
        $showtime = $this->showtime($movie, $room, $format);
        $seats = [];

        foreach ($positions as $position) {
            $seats[$position] = Seat::create([
                'room_id' => $room->id,
                'seat_code' => 'A'.$position,
                'row_number' => 1,
                'seat_number' => $position,
                'seat_type' => $position % 3 === 0 ? 'couple' : ($position % 2 === 0 ? 'vip' : 'standard'),
                'is_active' => true,
            ]);
        }

        return [$user, $showtime, $seats];
    }

    private function showtime(Movie $movie, Room $room, Format $format): Showtime
    {
        $startsAt = Carbon::now()->addDay();

        return Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($movie->duration),
            'price' => 80000,
            'status' => 'scheduled',
        ]);
    }

    private function booking(
        User $user,
        Showtime $showtime,
        Seat $seat,
        string $status,
        ?Carbon $holdExpiresAt = null
    ): Booking {
        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => 'YF-'.Str::upper(Str::random(12)),
            'total_amount' => 80000,
            'status' => $status,
            'payment_status' => $status === 'confirmed' ? 'paid' : 'pending',
            'hold_expires_at' => $holdExpiresAt,
        ]);
        BookingSeat::create([
            'booking_id' => $booking->id,
            'showtime_id' => $showtime->id,
            'seat_id' => $seat->id,
            'price' => 80000,
        ]);

        return $booking;
    }

    private function activeUser(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true])->save();

        return $user;
    }
}
