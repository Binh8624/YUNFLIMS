<?php

namespace App\Console\Commands;

use App\Models\Cinema;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeRules;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateTestShowtimes extends Command
{
    private const CLEANUP_MINUTES = 15;

    private const DAILY_TARGET_MIN = 3;

    private const DAILY_TARGET_MAX = 5;

    private const FIRST_START_TIME = '08:00';

    private const LAST_START_TIME = '23:00';

    private const TIME_INCREMENT_MINUTES = 30;

    private const DEFAULT_PRICES = [
        'price' => 80000,
        'vip_price' => 100000,
        'couple_price' => 160000,
    ];

    protected $signature = 'showtimes:generate-test
        {--force : Skip the interactive confirmation outside production}';

    protected $description = 'Generate a best-effort seven-day test schedule without replacing existing showtimes';

    public function handle(): int
    {
        if ($this->laravel->environment('production')) {
            $this->error('Không thể tạo suất chiếu thử nghiệm trong môi trường production.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Tạo lịch chiếu thử nghiệm trong 7 ngày tới? Lịch chiếu hiện có sẽ được giữ nguyên.',
            false
        )) {
            $this->info('Đã hủy, không có thay đổi nào được thực hiện.');

            return self::SUCCESS;
        }

        $report = DB::transaction(fn () => $this->generate());

        $this->newLine();
        $this->info('Hoàn tất tạo lịch chiếu thử nghiệm.');
        $this->line('Phim đang chiếu: '.$report['movie_count']);
        $this->line('Rạp: '.$report['cinema_count']);
        $this->line('Phòng đang hoạt động: '.$report['room_count']);
        $this->line('Suất chiếu đã tạo: '.$report['created_count']);
        $this->line('Suất chiếu bỏ qua: '.$report['skipped_count']);
        $this->line('Phim chưa có suất chiếu hợp lệ trong 7 ngày: '.$report['unallocated_movie_count']);

        if ($report['skipped_reasons'] !== []) {
            $this->newLine();
            $this->line('Lý do bỏ qua:');
            foreach ($report['skipped_reasons'] as $reason => $count) {
                $this->line("- {$reason}: {$count}");
            }
        }

        $this->newLine();
        $this->line('Suất chiếu trong 7 ngày theo ngày và rạp:');
        foreach ($report['daily_cinema_counts'] as $date => $cinemas) {
            $this->line($date.':');
            if ($cinemas === []) {
                $this->line('  Không có suất chiếu.');

                continue;
            }

            foreach ($cinemas as $cinemaName => $count) {
                $this->line("  {$cinemaName}: {$count}");
            }
        }

        Log::info('Test showtime generation completed', [
            'movie_count' => $report['movie_count'],
            'cinema_count' => $report['cinema_count'],
            'room_count' => $report['room_count'],
            'created_count' => $report['created_count'],
            'skipped_count' => $report['skipped_count'],
            'unallocated_movie_count' => $report['unallocated_movie_count'],
            'skipped_reasons' => $report['skipped_reasons'],
        ]);

        foreach ($report['skipped_reasons'] as $reason => $count) {
            Log::notice('Test showtime candidates skipped', [
                'reason' => $reason,
                'count' => $count,
            ]);
        }

        return self::SUCCESS;
    }

    private function generate(): array
    {
        $today = Carbon::today();
        $dates = collect(range(0, 6))->map(
            fn (int $offset) => $today->copy()->addDays($offset)
        );
        $now = Carbon::now();

        $cinemaCount = Cinema::query()->count();
        $rooms = Room::query()
            ->where('is_active', true)
            ->whereHas('cinema', fn ($query) => $query->where('is_active', true))
            ->with(['cinema', 'format'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $operationalRooms = $rooms->filter(fn (Room $room) => $room->format?->is_active
            && in_array($room->format->slug, ['2d', '3d', 'imax'], true));
        $roomsByFormat = $operationalRooms->groupBy('format_id');

        $movies = Movie::query()
            ->where('status', 'now_showing')
            ->with('format')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $roomIds = $operationalRooms->modelKeys();
        $existingShowtimes = $roomIds === []
            ? collect()
            : Showtime::query()
                ->whereIn('room_id', $roomIds)
                ->where('status', 'scheduled')
                ->with(['movie', 'room'])
                ->lockForUpdate()
                ->get();

        $schedulesByRoom = [];
        $roomLoadsByDate = [];
        $movieCountsByDate = [];
        $attemptedCountsByDate = [];
        $validMovieCounts = [];

        foreach ($dates as $date) {
            $key = $date->toDateString();
            $roomLoadsByDate[$key] = [];
            $movieCountsByDate[$key] = [];
            $attemptedCountsByDate[$key] = [];
            foreach ($operationalRooms as $room) {
                $roomLoadsByDate[$key][$room->id] = 0;
            }
        }

        foreach ($existingShowtimes as $showtime) {
            $schedulesByRoom[$showtime->room_id][] = [
                'start' => $showtime->starts_at,
                'end' => $showtime->ends_at,
            ];

            if ($showtime->starts_at->lt($now)) {
                continue;
            }

            $dateKey = $showtime->starts_at->toDateString();
            if (! isset($roomLoadsByDate[$dateKey][$showtime->room_id])) {
                continue;
            }

            $roomLoadsByDate[$dateKey][$showtime->room_id]++;

            if (
                $showtime->movie
                && $showtime->room
                && (int) $showtime->movie->format_id === (int) $showtime->room->format_id
                && (int) $showtime->format_id === (int) $showtime->movie->format_id
            ) {
                $movieCountsByDate[$dateKey][$showtime->movie_id] =
                    ($movieCountsByDate[$dateKey][$showtime->movie_id] ?? 0) + 1;
                $validMovieCounts[$showtime->movie_id] = ($validMovieCounts[$showtime->movie_id] ?? 0) + 1;
            }
        }

        $createdCount = 0;
        $skippedCount = 0;
        $skippedReasons = [];

        foreach ($dates as $dateIndex => $date) {
            $dateKey = $date->toDateString();

            $orderedMovies = $movies->isEmpty()
                ? collect()
                : $movies->slice($dateIndex % $movies->count())
                    ->concat($movies->take($dateIndex % $movies->count()))
                    ->values();

            for ($round = 0; $round < self::DAILY_TARGET_MAX; $round++) {
                foreach ($orderedMovies as $movie) {
                    $target = self::DAILY_TARGET_MIN + ($movie->id % 3);
                    $currentCount = $movieCountsByDate[$dateKey][$movie->id] ?? 0;
                    $attemptedCount = $attemptedCountsByDate[$dateKey][$movie->id] ?? 0;
                    if ($currentCount + $attemptedCount >= $target) {
                        continue;
                    }

                    $compatibleRooms = $roomsByFormat->get($movie->format_id, collect());
                    $reason = null;
                    if (
                        ! $movie->format
                        || ! $movie->format->is_active
                        || ! in_array($movie->format->slug, ['2d', '3d', 'imax'], true)
                    ) {
                        $reason = 'Phim không có định dạng hoạt động hợp lệ.';
                    } elseif ($compatibleRooms->isEmpty()) {
                        $reason = 'Không có phòng đang hoạt động cùng định dạng phim.';
                    } elseif ((int) $movie->duration <= 0) {
                        $reason = 'Thời lượng phim không hợp lệ.';
                    }

                    if ($reason !== null) {
                        $this->recordSkippedAttempt($reason, $skippedCount, $skippedReasons);
                        $attemptedCountsByDate[$dateKey][$movie->id] = $attemptedCount + 1;

                        continue;
                    }

                    $candidate = $this->findAvailableSlot(
                        $date,
                        $now,
                        $movie,
                        $compatibleRooms,
                        $schedulesByRoom,
                        $roomLoadsByDate[$dateKey]
                    );

                    if ($candidate === null) {
                        $this->recordSkippedAttempt(
                            'Không còn khung giờ phù hợp trong phòng tương thích (bao gồm 15 phút vệ sinh).',
                            $skippedCount,
                            $skippedReasons
                        );
                        $attemptedCountsByDate[$dateKey][$movie->id] = $attemptedCount + 1;

                        continue;
                    }

                    $showtime = Showtime::create([
                        'movie_id' => $movie->id,
                        'room_id' => $candidate['room']->id,
                        'format_id' => $movie->format_id,
                        'starts_at' => $candidate['start'],
                        'ends_at' => $candidate['end'],
                        ...self::DEFAULT_PRICES,
                        'status' => 'scheduled',
                    ]);

                    $schedulesByRoom[$candidate['room']->id][] = [
                        'start' => $showtime->starts_at,
                        'end' => $showtime->ends_at,
                    ];
                    $roomLoadsByDate[$dateKey][$candidate['room']->id]++;
                    $movieCountsByDate[$dateKey][$movie->id] = $currentCount + 1;
                    $validMovieCounts[$movie->id] = ($validMovieCounts[$movie->id] ?? 0) + 1;
                    $createdCount++;
                }
            }
        }

        $dailyCinemaCounts = $dates->mapWithKeys(
            fn (Carbon $date) => [$date->toDateString() => []]
        )->all();
        if ($roomIds !== []) {
            $scheduledInWindow = Showtime::query()
                ->whereIn('room_id', $roomIds)
                ->where('status', 'scheduled')
                ->whereBetween('starts_at', [
                    $dates->first()->copy()->startOfDay(),
                    $dates->last()->copy()->endOfDay(),
                ])
                ->with('room.cinema')
                ->get();

            foreach ($scheduledInWindow as $showtime) {
                if (! $showtime->room?->cinema) {
                    continue;
                }

                $dateKey = $showtime->starts_at->toDateString();
                $cinemaName = $showtime->room->cinema->name;
                $dailyCinemaCounts[$dateKey][$cinemaName] =
                    ($dailyCinemaCounts[$dateKey][$cinemaName] ?? 0) + 1;
            }
        }

        $unallocatedMovieCount = $movies
            ->filter(fn (Movie $movie) => ($validMovieCounts[$movie->id] ?? 0) === 0)
            ->count();

        return [
            'movie_count' => $movies->count(),
            'cinema_count' => $cinemaCount,
            'room_count' => $operationalRooms->count(),
            'created_count' => $createdCount,
            'skipped_count' => $skippedCount,
            'skipped_reasons' => $skippedReasons,
            'unallocated_movie_count' => $unallocatedMovieCount,
            'daily_cinema_counts' => $dailyCinemaCounts,
        ];
    }

    private function findAvailableSlot(
        Carbon $date,
        Carbon $now,
        Movie $movie,
        $compatibleRooms,
        array $schedulesByRoom,
        array $roomLoads
    ): ?array {
        $rooms = $compatibleRooms
            ->sortBy(fn (Room $room) => [$roomLoads[$room->id] ?? 0, $room->id])
            ->values();
        $firstStart = $date->copy()->setTimeFromTimeString(self::FIRST_START_TIME);
        $lastStart = $date->copy()->setTimeFromTimeString(self::LAST_START_TIME);

        foreach ($rooms as $room) {
            for ($start = $firstStart->copy(); $start->lte($lastStart); $start->addMinutes(self::TIME_INCREMENT_MINUTES)) {
                if ($start->lt($now)) {
                    continue;
                }

                $end = $start->copy()->addMinutes((int) $movie->duration);
                $conflicts = collect($schedulesByRoom[$room->id] ?? [])
                    ->contains(fn (array $schedule) => ShowtimeRules::overlaps(
                        $start,
                        $end,
                        $schedule['start'],
                        $schedule['end'],
                        self::CLEANUP_MINUTES
                    ));

                if (! $conflicts) {
                    return ['room' => $room, 'start' => $start, 'end' => $end];
                }
            }
        }

        return null;
    }

    private function recordSkippedAttempt(string $reason, int &$skippedCount, array &$skippedReasons): void
    {
        $skippedCount++;
        $skippedReasons[$reason] = ($skippedReasons[$reason] ?? 0) + 1;
    }
}
