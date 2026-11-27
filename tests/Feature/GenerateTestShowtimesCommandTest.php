<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeRules;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class GenerateTestShowtimesCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_compatible_schedules_without_overlaps_and_is_repeatable(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 06:00:00'));
        $format = Format::where('slug', '2d')->firstOrFail();
        Movie::where('status', 'now_showing')->update(['status' => 'inactive']);
        Room::where('is_active', true)->update(['is_active' => false]);
        $movie = $this->movie($format);
        $room = $this->room($format);

        $this->assertSame(0, Artisan::call('showtimes:generate-test', ['--force' => true]));

        $generated = Showtime::where('movie_id', $movie->id)
            ->where('starts_at', '>=', '2026-11-12 00:00:00')
            ->where('starts_at', '<', '2026-11-19 00:00:00')
            ->with('room')
            ->orderBy('starts_at')
            ->get();

        $this->assertGreaterThanOrEqual(3, $generated->count(), Artisan::output());
        $this->assertSame([$room->id], $generated->pluck('room_id')->unique()->all());
        foreach ($generated->groupBy(fn (Showtime $showtime) => $showtime->starts_at->toDateString()) as $dailyShowtimes) {
            $this->assertGreaterThanOrEqual(3, $dailyShowtimes->count());
            $this->assertLessThanOrEqual(5, $dailyShowtimes->count());
        }
        $this->assertSame(
            $generated->count(),
            Showtime::where('movie_id', $movie->id)
                ->where('room_id', $room->id)
                ->where('starts_at', '>=', '2026-11-12 00:00:00')
                ->where('starts_at', '<', '2026-11-19 00:00:00')
                ->count()
        );

        foreach ($generated as $showtime) {
            $this->assertSame($format->id, $showtime->format_id);
            $this->assertSame('scheduled', $showtime->status);
            $this->assertSame(80000.0, (float) $showtime->price);
            $this->assertSame(100000.0, (float) $showtime->vip_price);
            $this->assertSame(160000.0, (float) $showtime->couple_price);
            $this->assertGreaterThanOrEqual('08:00', $showtime->starts_at->format('H:i'));
            $this->assertLessThanOrEqual('23:00', $showtime->starts_at->format('H:i'));
            $this->assertSame(
                $showtime->starts_at->copy()->addMinutes($movie->duration)->toDateTimeString(),
                $showtime->ends_at->toDateTimeString()
            );
        }

        foreach ($generated->groupBy('room_id') as $roomShowtimes) {
            foreach ($roomShowtimes->values() as $index => $showtime) {
                if ($index === 0) {
                    continue;
                }

                $previous = $roomShowtimes[$index - 1];
                $this->assertFalse(ShowtimeRules::overlaps(
                    $previous->starts_at,
                    $previous->ends_at,
                    $showtime->starts_at,
                    $showtime->ends_at
                ));
            }
        }

        $countBeforeRerun = Showtime::where('movie_id', $movie->id)
            ->where('starts_at', '>=', '2026-11-12 00:00:00')
            ->where('starts_at', '<', '2026-11-19 00:00:00')
            ->count();

        $this->artisan('showtimes:generate-test', ['--force' => true])
            ->assertExitCode(0);

        $this->assertSame(
            $countBeforeRerun,
            Showtime::where('movie_id', $movie->id)
                ->where('starts_at', '>=', '2026-11-12 00:00:00')
                ->where('starts_at', '<', '2026-11-19 00:00:00')
                ->count()
        );

        $duplicateSlots = Showtime::query()
            ->where('movie_id', $movie->id)
            ->where('starts_at', '>=', '2026-11-12 00:00:00')
            ->where('starts_at', '<', '2026-11-19 00:00:00')
            ->select('room_id', 'starts_at')
            ->groupBy('room_id', 'starts_at')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->assertCount(0, $duplicateSlots);
    }

    public function test_it_skips_movies_when_no_active_room_has_a_matching_format(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 06:00:00'));
        Movie::where('status', 'now_showing')->update(['status' => 'inactive']);
        Room::where('is_active', true)->update(['is_active' => false]);
        $movie = $this->movie(Format::where('slug', '2d')->firstOrFail());
        $this->room(Format::where('slug', '3d')->firstOrFail());

        $this->artisan('showtimes:generate-test', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('showtimes', ['movie_id' => $movie->id]);
    }

    public function test_it_respects_existing_schedules_and_the_cleanup_gap(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-12 06:00:00'));
        Movie::where('status', 'now_showing')->update(['status' => 'inactive']);
        Room::where('is_active', true)->update(['is_active' => false]);
        $format = Format::where('slug', '2d')->firstOrFail();
        $movie = $this->movie($format);
        $room = $this->room($format);
        $existingStart = Carbon::parse('2026-11-13 08:00:00');
        $existing = Showtime::create([
            'movie_id' => $movie->id,
            'room_id' => $room->id,
            'format_id' => $format->id,
            'starts_at' => $existingStart,
            'ends_at' => $existingStart->copy()->addMinutes($movie->duration),
            'price' => 80000,
            'status' => 'scheduled',
        ]);

        $this->artisan('showtimes:generate-test', ['--force' => true])
            ->assertExitCode(0);

        $sameDayShowtimes = Showtime::where('room_id', $room->id)
            ->whereDate('starts_at', '2026-11-13')
            ->orderBy('starts_at')
            ->get();

        $this->assertSame($existing->id, $sameDayShowtimes->first()->id);
        $this->assertGreaterThanOrEqual(2, $sameDayShowtimes->count());
        $this->assertGreaterThanOrEqual('10:00', $sameDayShowtimes[1]->starts_at->format('H:i'));

        foreach ($sameDayShowtimes->values() as $index => $showtime) {
            if ($index === 0) {
                continue;
            }

            $previous = $sameDayShowtimes[$index - 1];
            $this->assertFalse(ShowtimeRules::overlaps(
                $previous->starts_at,
                $previous->ends_at,
                $showtime->starts_at,
                $showtime->ends_at
            ));
        }
    }

    public function test_it_refuses_to_run_in_production_even_with_force(): void
    {
        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->artisan('showtimes:generate-test', ['--force' => true])
                ->expectsOutputToContain('Không thể tạo suất chiếu thử nghiệm trong môi trường production.')
                ->assertExitCode(1);
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    private function movie(Format $format): Movie
    {
        return Movie::create([
            'title' => 'Generator test '.Str::random(8),
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
            'name' => 'Generator test cinema '.Str::random(8),
            'address' => 'Test address',
            'city' => 'Hà Nội',
            'is_active' => true,
        ]);

        return Room::create([
            'cinema_id' => $cinema->id,
            'room_code' => 'T'.Str::upper(Str::random(10)),
            'name' => 'Generator test room',
            'format_id' => $format->id,
            'total_seats' => 60,
            'is_active' => true,
        ]);
    }
}
