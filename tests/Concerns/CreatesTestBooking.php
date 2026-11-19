<?php

namespace Tests\Concerns;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait CreatesTestBooking
{
    public function makeBooking(): Booking
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => true, 'loyalty_points' => 0])->save();
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = Cinema::create([
            'name' => 'Pay cinema ' . Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'P' . Str::upper(Str::random(10)),
            'name' => 'Pay room',
            'total_seats' => 4,
            'is_active' => true,
        ]);
        $room->formats()->attach($format);
        $movie = Movie::create([
            'title' => 'Pay movie ' . Str::random(8),
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
        $seats = collect(range(1, 4))->map(fn (int $number) => Seat::create([
            'room_id' => $room->id,
            'seat_code' => 'A' . $number,
            'row_number' => 1,
            'seat_number' => $number,
            'seat_type' => 'standard',
            'is_active' => true,
        ]));

        $booking = Booking::create([
            'user_id' => $user->id,
            'showtime_id' => $showtime->id,
            'booking_code' => Booking::generateBookingCode(),
            'total_amount' => 160000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'hold_expires_at' => now()->addMinutes(5),
            'qr_code' => Booking::generateQrSvg('test-' . Str::random(6)),
            'ticket_status' => 'available',
        ]);
        foreach ($seats->take(2) as $seat) {
            BookingSeat::create([
                'booking_id' => $booking->id,
                'showtime_id' => $showtime->id,
                'seat_id' => $seat->id,
                'price' => 80000,
            ]);
        }

        return $booking;
    }

}
