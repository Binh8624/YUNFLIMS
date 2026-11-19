<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Booking;
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

class BookingSeatValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seat_selector_shows_the_price_for_each_seat_type(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true]);
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Test room',
            'total_seats' => 3,
            'is_active' => true,
        ]);
        $room->formats()->attach($format);

        $movie = Movie::create([
            'title' => 'Test movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
        ]);
        $movie->formats()->attach($format);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addMinutes(90),
            'price' => 80000,
            'vip_price' => 100000,
            'couple_price' => 160000,
            'status' => 'scheduled',
        ]);

        collect([
            ['seat_code' => 'A1', 'seat_type' => 'standard'],
            ['seat_code' => 'A2', 'seat_type' => 'vip'],
            ['seat_code' => 'A3', 'seat_type' => 'couple'],
        ])->each(fn (array $seat, int $index) => Seat::create([
            'room_id' => $room->id,
            'seat_code' => $seat['seat_code'],
            'row_number' => 1,
            'seat_number' => $index + 1,
            'seat_type' => $seat['seat_type'],
            'is_active' => true,
        ]));

        $this->actingAs($user)
            ->get(route('bookings.seats', $showtime))
            ->assertOk()
            ->assertSee('80.000 đ')
            ->assertSee('100.000 đ')
            ->assertSee('160.000 đ');
    }

    public function test_broken_seat_cannot_be_booked(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true]);
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Test room',
            'total_seats' => 1,
            'is_active' => true,
        ]);
        $room->formats()->attach($format);

        $movie = Movie::create([
            'title' => 'Test movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
        ]);
        $movie->formats()->attach($format);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addMinutes(90),
            'price' => 80000,
            'status' => 'scheduled',
        ]);
        $brokenSeat = Seat::create([
            'room_id' => $room->id,
            'seat_code' => 'A1',
            'row_number' => 1,
            'seat_number' => 1,
            'seat_type' => 'standard',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('bookings.seats', $showtime))
            ->assertOk()
            ->assertSee('A1')
            ->assertSee('Khóa / hỏng');

        $this->actingAs($user)
            ->post(route('bookings.store', $showtime), ['seats' => [$brokenSeat->id]])
            ->assertSessionHasErrors('seats');
    }

    public function test_booking_uses_server_price_and_mock_payment_confirms_that_amount(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true]);
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Test room',
            'total_seats' => 1,
            'is_active' => true,
        ]);
        $room->formats()->attach($format);

        $movie = Movie::create([
            'title' => 'Test movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
        ]);
        $movie->formats()->attach($format);

        $showtime = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addMinutes(90),
            'price' => 80000,
            'status' => 'scheduled',
        ]);
        $seat = Seat::create([
            'room_id' => $room->id,
            'seat_code' => 'A1',
            'row_number' => 1,
            'seat_number' => 1,
            'seat_type' => 'standard',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('bookings.store', $showtime), [
                'seats' => [$seat->id],
                'total_amount' => 1,
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $booking = Booking::where('user_id', $user->id)
            ->where('showtime_id', $showtime->id)
            ->firstOrFail();

        $this->assertSame('80000.00', $booking->total_amount);
        $this->assertSame('pending', $booking->status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertSame('80000.00', $booking->bookingSeats()->firstOrFail()->price);

        $this->from(route('bookings.payment', $booking))
            ->post(route('bookings.payment.process', $booking), [
                'payment_method' => 'mock_test',
            ])
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('mock_test', $booking->payment_method);
        $this->assertSame('available', $booking->ticket_status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'total_amount' => 80000,
        ]);
    }
}
