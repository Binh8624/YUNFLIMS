<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Database\Seeders\CinemaSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormatCompatibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schedule_creation_rejects_a_room_with_a_different_format(): void
    {
        $admin = $this->admin();
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $movie = $this->movie($twoD);
        $room = $this->room($threeD);

        $this->actingAs($admin)
            ->from(route('admin.showtimes.create'))
            ->post(route('admin.showtimes.store'), $this->showtimeData($movie, $room, $twoD))
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseMissing('showtimes', [
            'movie_id' => $movie->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_schedule_update_rejects_a_new_movie_with_a_different_format(): void
    {
        $admin = $this->admin();
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $movie = $this->movie($twoD);
        $otherMovie = $this->movie($threeD);
        $room = $this->room($twoD);
        $showtime = $this->showtime($movie, $room, $twoD);

        $this->actingAs($admin)
            ->from(route('admin.showtimes.edit', $showtime))
            ->put(route('admin.showtimes.update', $showtime), $this->showtimeData($otherMovie, $room, $threeD))
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseHas('showtimes', [
            'id' => $showtime->id,
            'movie_id' => $movie->id,
            'room_id' => $room->id,
        ]);
    }

    public function test_room_format_cannot_change_when_its_scheduled_showtime_would_become_incompatible(): void
    {
        $admin = $this->admin();
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $movie = $this->movie($twoD);
        $room = $this->room($twoD);
        $this->showtime($movie, $room, $twoD);

        $this->actingAs($admin)
            ->from(route('admin.rooms.edit', $room))
            ->put(route('admin.rooms.update', $room), [
                'cinema_id' => $room->cinema_id,
                'room_code' => $room->room_code,
                'name' => $room->name,
                'format_id' => $threeD->id,
                'total_seats' => $room->total_seats,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('format_id');

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'format_id' => $twoD->id,
        ]);
    }

    public function test_movie_format_cannot_change_when_its_scheduled_showtime_would_become_incompatible(): void
    {
        $admin = $this->admin();
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $movie = $this->movie($twoD);
        $room = $this->room($twoD);
        $this->showtime($movie, $room, $twoD);

        $this->actingAs($admin)
            ->from(route('admin.movies.edit', $movie))
            ->put(route('admin.movies.update', $movie), [
                'title' => $movie->title,
                'genre' => $movie->genre,
                'duration' => $movie->duration,
                'age_rating' => $movie->age_rating,
                'status' => $movie->status,
                'format_id' => $threeD->id,
            ])
            ->assertSessionHasErrors('format_id');

        $this->assertDatabaseHas('movies', [
            'id' => $movie->id,
            'format_id' => $twoD->id,
        ]);
    }

    public function test_customer_cannot_book_a_showtime_with_a_mismatched_room_format(): void
    {
        $customer = User::factory()->create();
        $customer->forceFill(['is_active' => true])->save();
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $movie = $this->movie($twoD);
        $room = $this->room($threeD);
        $showtime = $this->showtime($movie, $room, $twoD);
        $seat = Seat::create([
            'room_id' => $room->id,
            'seat_code' => 'A1',
            'row_number' => 1,
            'seat_number' => 1,
            'seat_type' => 'standard',
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('bookings.store', $showtime), ['seats' => [$seat->id]])
            ->assertSessionHasErrors('seats');

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $customer->id,
            'showtime_id' => $showtime->id,
        ]);
    }

    public function test_cinema_seeder_adds_five_rooms_only_once(): void
    {
        $seeder = new CinemaSeeder;
        $seeder->run();
        $cinema = Cinema::where('name', 'YUNFLIMS Hà Nội')->firstOrFail();
        $firstCount = Room::where('cinema_id', $cinema->id)->count();

        $seeder->run();

        $this->assertSame($firstCount, Room::where('cinema_id', $cinema->id)->count());
        $this->assertGreaterThanOrEqual(6, $firstCount);
        $rooms = Room::where('cinema_id', $cinema->id)
            ->whereIn('name', ['Phòng 02', 'Phòng 03', 'Phòng 04', 'Phòng 05', 'Phòng 06'])
            ->with(['format', 'seats'])
            ->get();
        $this->assertCount(5, $rooms);
        $this->assertEquals(
            ['2d' => 2, '3d' => 2, 'imax' => 1],
            $rooms->countBy(fn (Room $room) => $room->format->slug)->all()
        );
        $this->assertSame($rooms->count(), $rooms->pluck('room_code')->unique()->count());
        $this->assertSame(60, $rooms->min(fn (Room $room) => $room->seats->count()));
        foreach ($rooms as $room) {
            $this->assertSame(20, $room->seats->where('seat_type', 'vip')->count());
        }
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->forceFill(['is_active' => true])->save();

        return $admin;
    }

    private function movie(Format $format): Movie
    {
        return Movie::create([
            'title' => 'Format test '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
            'format_id' => $format->id,
        ]);
    }

    private function room(Format $format): Room
    {
        $cinema = Cinema::create([
            'name' => 'Format test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);

        return Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Format test room',
            'format_id' => $format->id,
            'total_seats' => 60,
            'is_active' => true,
        ]);
    }

    private function showtime(Movie $movie, Room $room, Format $format): Showtime
    {
        $startsAt = Carbon::now()->addDays(2);

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

    private function showtimeData(Movie $movie, Room $room, Format $format): array
    {
        return [
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_on' => Carbon::tomorrow()->toDateString(),
            'starts_time' => '18:00',
            'price' => 80000,
            'vip_price' => 100000,
            'couple_price' => 160000,
            'status' => 'scheduled',
        ];
    }
}
