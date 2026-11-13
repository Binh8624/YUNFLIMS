<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_filter_showtimes_by_movie_date_cinema_and_room(): void
    {
        $admin = $this->admin();
        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = $this->cinema();
        $room = $this->room($cinema, $format);
        $movie = $this->movie($format);
        $showtime = $this->showtime($movie, $room, Carbon::tomorrow()->setTime(18, 30));

        $otherRoom = $this->room($this->cinema(), $format);
        $otherMovie = $this->movie($format);
        $this->showtime($otherMovie, $otherRoom, Carbon::tomorrow()->setTime(20, 0));

        $this->actingAs($admin)
            ->get(route('admin.showtimes.index', [
                'movie_id' => $movie->id,
                'date' => $showtime->starts_at->toDateString(),
                'cinema_id' => $cinema->id,
                'room_id' => $room->id,
            ]))
            ->assertOk()
            ->assertViewHas('showtimes', fn ($showtimes) => $showtimes->total() === 1
                && $showtimes->getCollection()->first()->is($showtime));
    }

    public function test_auto_allocation_assigns_one_compatible_free_room_per_requested_slot(): void
    {
        $admin = $this->admin();
        Room::where('is_active', true)->update(['is_active' => false]);

        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $cinema = $this->cinema();
        $occupiedRoom = $this->room($cinema, $twoD);
        $firstFreeRoom = $this->room($cinema, $twoD);
        $secondFreeRoom = $this->room($cinema, $twoD);
        $this->room($cinema, $threeD);
        $movie = $this->movie($twoD);
        $startDate = Carbon::tomorrow()->toDateString();
        $this->showtime($movie, $occupiedRoom, Carbon::parse($startDate.' 09:00'));

        $data = [
            'movie_id' => $movie->id,
            'format_id' => $twoD->id,
            'auto_allocate' => 1,
            'starts_on' => $startDate,
            'ends_on' => $startDate,
            'times' => ['09:00', '10:00'],
            'price' => 80000,
            'vip_price' => 100000,
            'couple_price' => 160000,
        ];

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.showtimes.auto.preview'), $data)
            ->assertOk()
            ->assertViewHas('preview', fn (array $plan) => $plan['count'] === 2
                && $plan['skipped'] === []
                && $plan['accepted'][0]['room_id'] === $firstFreeRoom->id
                && $plan['accepted'][1]['room_id'] === $secondFreeRoom->id)
            ->assertSee('Tự động chọn một phòng tương thích');

        $this->actingAs($admin)
            ->post(route('admin.showtimes.auto.store'), $data)
            ->assertRedirect(route('admin.showtimes.index'))
            ->assertSessionHas('success');

        $created = Showtime::where('movie_id', $movie->id)
            ->whereDate('starts_at', $startDate)
            ->where('id', '!=', Showtime::where('room_id', $occupiedRoom->id)->value('id'))
            ->orderBy('starts_at')
            ->get();

        $this->assertCount(2, $created);
        $this->assertSame(
            [$firstFreeRoom->id, $secondFreeRoom->id],
            $created->pluck('room_id')->all()
        );
    }

    public function test_manual_bulk_schedule_still_creates_each_time_in_every_selected_room(): void
    {
        $admin = $this->admin();
        Room::where('is_active', true)->update(['is_active' => false]);

        $format = Format::where('slug', '2d')->firstOrFail();
        $cinema = $this->cinema();
        $firstRoom = $this->room($cinema, $format);
        $secondRoom = $this->room($cinema, $format);
        $movie = $this->movie($format);
        $startDate = Carbon::tomorrow()->toDateString();
        $data = [
            'movie_id' => $movie->id,
            'format_id' => $format->id,
            'room_ids' => [$firstRoom->id, $secondRoom->id],
            'starts_on' => $startDate,
            'ends_on' => $startDate,
            'times' => ['18:00'],
            'price' => 80000,
            'vip_price' => 100000,
            'couple_price' => 160000,
        ];

        $this->actingAs($admin)
            ->post(route('admin.showtimes.auto.store'), $data)
            ->assertRedirect(route('admin.showtimes.index'));

        $this->assertSame(
            [$firstRoom->id, $secondRoom->id],
            Showtime::where('movie_id', $movie->id)
                ->whereDate('starts_at', $startDate)
                ->orderBy('room_id')
                ->pluck('room_id')
                ->all()
        );
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->forceFill(['is_active' => true])->save();

        return $admin;
    }

    private function cinema(): Cinema
    {
        return Cinema::create([
            'name' => 'Schedule test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);
    }

    private function room(Cinema $cinema, Format $format): Room
    {
        return Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Schedule test room '.Str::random(5),
            'format_id' => $format->id,
            'total_seats' => 60,
            'is_active' => true,
        ]);
    }

    private function movie(Format $format): Movie
    {
        return Movie::create([
            'title' => 'Schedule test movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
            'format_id' => $format->id,
        ]);
    }

    private function showtime(Movie $movie, Room $room, Carbon $startsAt): Showtime
    {
        return Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $movie->format_id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($movie->duration),
            'price' => 80000,
            'status' => 'scheduled',
        ]);
    }
}
