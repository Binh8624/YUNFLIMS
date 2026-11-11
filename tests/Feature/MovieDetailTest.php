<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MovieDetailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_active_movie_detail_renders_schedule_and_reviews(): void
    {
        $movie = Movie::where('status', 'now_showing')->firstOrFail();

        $this->get(route('movies.show', $movie))
            ->assertOk()
            ->assertSee('Đánh giá phim')
            ->assertSee('Chọn ngày và rạp');
    }

    public function test_movie_detail_hides_showtimes_for_unsupported_room_formats(): void
    {
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
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
        $room->formats()->attach($twoD);

        $movie = Movie::create([
            'title' => 'Test movie '.Str::random(8),
            'genre' => 'Drama',
            'duration' => 90,
            'status' => 'now_showing',
            'age_rating' => 'P',
        ]);
        $movie->formats()->attach($threeD);

        Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $threeD->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addMinutes(90),
            'price' => 80000,
            'status' => 'scheduled',
        ]);

        $this->get(route('movies.show', $movie))
            ->assertOk()
            ->assertViewHas('movie', fn (Movie $renderedMovie) => $renderedMovie->showtimes->isEmpty());
    }
}
