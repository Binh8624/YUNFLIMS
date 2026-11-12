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

class CinemaDiscoveryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schedule_is_grouped_and_filters_by_real_city_and_format_data(): void
    {
        $twoD = Format::where('slug', '2d')->firstOrFail();
        $threeD = Format::where('slug', '3d')->firstOrFail();
        $startsAt = Carbon::tomorrow()->setTime(18, 30);
        $hanoi = $this->showtime($this->movie($twoD, 'Hành động, Khoa học viễn tưởng'), 'Hà Nội', $twoD, $startsAt);
        $danang = $this->showtime($this->movie($threeD, 'Hoạt hình, Gia đình'), 'Đà Nẵng', $threeD, $startsAt);

        $this->get(route('showtimes.index', [
            'date' => $startsAt->toDateString(),
            'city' => 'Hà Nội',
            'format' => '2d',
        ]))
            ->assertOk()
            ->assertSee($hanoi->movie->title)
            ->assertSee('Hà Nội')
            ->assertSee(route('bookings.seats', $hanoi))
            ->assertDontSee($danang->movie->title);
    }

    public function test_movie_filter_can_be_removed_and_genre_filter_matches_a_genre_token(): void
    {
        $format = Format::where('slug', '2d')->firstOrFail();
        $startsAt = Carbon::tomorrow()->setTime(19, 0);
        $actionMovie = $this->movie($format, 'Hành động, Khoa học viễn tưởng');
        $familyMovie = $this->movie($format, 'Hoạt hình, Gia đình');
        $this->showtime($actionMovie, 'Hà Nội', $format, $startsAt);
        $this->showtime($familyMovie, 'Hà Nội', $format, $startsAt->copy()->addHour());

        $this->get(route('showtimes.index', [
            'date' => $startsAt->toDateString(),
            'movie_id' => $actionMovie->id,
            'genre' => 'Hành động',
        ]))
            ->assertOk()
            ->assertSee($actionMovie->title)
            ->assertDontSee($familyMovie->title)
            ->assertSee('Xem tất cả phim');

        $this->get(route('showtimes.index', [
            'date' => $startsAt->toDateString(),
            'genre' => 'Gia đình',
        ]))
            ->assertOk()
            ->assertSee($familyMovie->title)
            ->assertDontSee($actionMovie->title);
    }

    public function test_home_and_movie_buy_actions_use_the_same_central_schedule(): void
    {
        $movie = $this->movie(Format::where('slug', '2d')->firstOrFail(), 'Tâm lý');
        $movie->update(['is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-home-carousel', false)
            ->assertSee('Phim hot')
            ->assertSee($movie->title)
            ->assertSee(route('showtimes.index', ['movie_id' => $movie->id]));

        $this->get(route('movies.show', $movie))
            ->assertOk()
            ->assertSee(route('showtimes.index', ['movie_id' => $movie->id]));
    }

    public function test_unverified_ratings_are_not_claimed_or_offered_as_age_filters(): void
    {
        $movie = $this->movie(Format::where('slug', '2d')->firstOrFail(), 'Hành động');

        $this->assertFalse($movie->age_rating_verified);
        $this->assertSame('Chưa xác nhận', $movie->ageRatingLabel());

        $this->get(route('movies.index'))
            ->assertOk()
            ->assertSee('Chưa xác nhận')
            ->assertDontSee('<option value="T13">', false);
    }

    public function test_only_real_future_show_dates_are_offered(): void
    {
        $format = Format::where('slug', '2d')->firstOrFail();
        $startsAt = Carbon::tomorrow()->setTime(20, 0);
        $this->showtime($this->movie($format, 'Phiêu lưu'), 'Hà Nội', $format, $startsAt);

        $this->get(route('showtimes.index'))
            ->assertOk()
            ->assertViewHas('showDates', fn ($dates) => $dates->contains($startsAt->toDateString()));
    }

    public function test_prohibited_classification_is_not_publicly_listed_or_bookable(): void
    {
        $format = Format::where('slug', '2d')->firstOrFail();
        $movie = $this->movie($format, 'Chính kịch');
        $movie->update([
            'age_rating' => 'C',
            'age_rating_verified' => true,
        ]);
        $showtime = $this->showtime($movie, 'Hà Nội', $format, Carbon::tomorrow()->setTime(17, 0));
        $seatId = $showtime->room->seats()->value('id');
        $customer = User::factory()->create();
        $customer->forceFill(['is_active' => true])->save();

        $this->get(route('movies.show', $movie))->assertNotFound();
        $this->get(route('showtimes.index', ['date' => $showtime->starts_at->toDateString()]))
            ->assertDontSee($movie->title);
        $this->actingAs($customer)
            ->post(route('bookings.store', $showtime), ['seats' => [$seatId]])
            ->assertSessionHasErrors('seats');

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $customer->id,
            'showtime_id' => $showtime->id,
        ]);
    }

    private function movie(Format $format, string $genre): Movie
    {
        return Movie::create([
            'title' => 'Discovery '.Str::random(10),
            'genre' => $genre,
            'duration' => 100,
            'status' => 'now_showing',
            'poster' => 'https://example.test/poster.jpg',
            'age_rating' => 'T13',
            'format_id' => $format->id,
        ]);
    }

    private function showtime(Movie $movie, string $city, Format $format, Carbon $startsAt): Showtime
    {
        $cinema = Cinema::create([
            'name' => 'Discovery Cinema '.Str::random(8),
            'address' => 'Cinema address',
            'city' => $city,
            'is_active' => true,
        ]);
        $room = Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'D'.Str::upper(Str::random(10)),
            'name' => 'Room A',
            'format_id' => $format->id,
            'total_seats' => 1,
            'is_active' => true,
        ]);
        Seat::create([
            'room_id' => $room->id,
            'seat_code' => 'A1',
            'row_number' => 1,
            'seat_number' => 1,
            'seat_type' => 'standard',
            'is_active' => true,
        ]);

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
}
