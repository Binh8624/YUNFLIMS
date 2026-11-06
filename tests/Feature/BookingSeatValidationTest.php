<?php

namespace Tests\Feature;

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

class BookingSeatValidationTest extends TestCase
{
    use DatabaseTransactions;

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
}
