<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use App\Services\ShowtimeRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleGeneratorController extends Controller
{
    private const CLEANUP_MINUTES = 15;

    public function index()
    {
        return view('admin.showtimes.auto', [
            'movies' => Movie::with('format')->where('status', '!=', 'inactive')->orderBy('title')->get(),
            'rooms' => Room::with(['cinema', 'format'])
                ->where('is_active', true)
                ->whereHas('cinema', fn ($query) => $query->where('is_active', true))
                ->orderBy('name')
                ->get(),
            'formats' => Format::whereIn('slug', ['2d', '3d', 'imax'])->where('is_active', true)->orderBy('name')->get(),
            'preview' => session('schedule_preview'),
        ]);
    }

    public function preview(Request $request)
    {
        $data = $this->validated($request);
        $plan = $this->buildPlan($data, $this->roomsFor($data));

        return redirect()->route('admin.showtimes.auto')
            ->withInput()
            ->with('schedule_preview', $plan);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $result = DB::transaction(function () use ($data) {
            $rooms = $this->roomsFor($data, true);
            Movie::whereKey($data['movie_id'])->lockForUpdate()->firstOrFail();

            $plan = $this->buildPlan($data, $rooms, true);

            $created = collect($plan['accepted'])->map(fn (array $candidate) => Showtime::create([
                'movie_id' => $data['movie_id'],
                'room_id' => $candidate['room_id'],
                'format_id' => $data['format_id'],
                'starts_at' => $candidate['starts_at'],
                'ends_at' => $candidate['ends_at'],
                'price' => $data['price'],
                'vip_price' => $data['vip_price'],
                'couple_price' => $data['couple_price'],
                'status' => 'scheduled',
            ]))->all();

            return ['created' => $created, 'skipped' => count($plan['skipped'])];
        });

        return redirect()->route('admin.showtimes.index')
            ->with(
                'success',
                count($result['created']).' suất chiếu được tạo; '.$result['skipped'].' suất bị bỏ qua.'
            );
    }

    private function validated(Request $request): array
    {
        $rules = [
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
            'format_id' => ['required', 'integer', 'exists:formats,id,is_active,1'],
            'auto_allocate' => ['nullable', 'boolean'],
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'times' => ['required', 'array', 'min:1', 'max:8'],
            'times.*' => ['required', 'date_format:H:i', 'distinct'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'vip_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'couple_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];

        if (! $request->boolean('auto_allocate')) {
            $rules['room_ids'] = ['required', 'array', 'min:1', 'max:10'];
            $rules['room_ids.*'] = ['required', 'integer', 'distinct', 'exists:rooms,id'];
        }

        $data = $request->validate($rules);
        $data['auto_allocate'] = $request->boolean('auto_allocate');

        $days = Carbon::parse($data['starts_on'])->diffInDays(Carbon::parse($data['ends_on'])) + 1;
        $requestedSlotsPerDay = count($data['times'])
            * ($data['auto_allocate'] ? 1 : count($data['room_ids']));
        if ($days > 31 || $days * $requestedSlotsPerDay > 500) {
            throw ValidationException::withMessages([
                'ends_on' => 'Tạo lịch tối đa 31 ngày và 500 khung giờ cùng lúc.',
            ]);
        }

        return $data;
    }

    private function roomsFor(array $data, bool $lockForUpdate = false): Collection
    {
        $rooms = Room::with(['cinema', 'format']);

        if ($data['auto_allocate']) {
            $rooms
                ->where('is_active', true)
                ->where('format_id', $data['format_id'])
                ->whereHas('cinema', fn ($query) => $query->where('is_active', true));
        } else {
            $rooms->whereIn('id', $data['room_ids']);
        }

        return $rooms
            ->orderBy('id')
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->get();
    }

    private function buildPlan(array $data, Collection $rooms, bool $lockExisting = false): array
    {
        $movie = Movie::with('format')->findOrFail($data['movie_id']);
        $format = Format::findOrFail($data['format_id']);

        if (
            $movie->status === 'inactive'
            || ! $format->is_active
            || ! in_array($format->slug, ['2d', '3d', 'imax'], true)
            || (int) $movie->format_id !== (int) $format->id
        ) {
            throw ValidationException::withMessages([
                'format_id' => 'Định dạng lịch chiếu phải trùng với định dạng của phim.',
            ]);
        }

        $compatibleRooms = $rooms->filter(fn (Room $room) => $room->is_active
            && $room->cinema?->is_active
            && (int) $room->format_id === (int) $format->id);

        if ($compatibleRooms->isEmpty()) {
            throw ValidationException::withMessages([
                $data['auto_allocate'] ? 'format_id' : 'room_ids' => 'Không có phòng đang hoạt động hỗ trợ định dạng này.',
            ]);
        }
        if (! $data['auto_allocate'] && $compatibleRooms->count() !== $rooms->count()) {
            throw ValidationException::withMessages([
                'room_ids' => 'Một hoặc nhiều phòng đã chọn không hoạt động hoặc không hỗ trợ định dạng này.',
            ]);
        }

        $accepted = [];
        $skipped = [];
        $startDate = Carbon::parse($data['starts_on'])->startOfDay();
        $endDate = Carbon::parse($data['ends_on'])->startOfDay();
        $times = collect($data['times'])->sort()->values();
        $windowStart = Carbon::parse($startDate->toDateString().' '.$times->first())
            ->subMinutes(self::CLEANUP_MINUTES);
        $windowEnd = Carbon::parse($endDate->toDateString().' '.$times->last())
            ->addMinutes((int) $movie->duration + self::CLEANUP_MINUTES);
        $existingShowtimes = Showtime::query()
            ->whereIn('room_id', $compatibleRooms->pluck('id'))
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $windowEnd->toDateTimeString())
            ->where('ends_at', '>', $windowStart->toDateTimeString())
            ->when($lockExisting, fn ($query) => $query->lockForUpdate())
            ->get(['room_id', 'starts_at', 'ends_at']);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            foreach ($times as $time) {
                $startsAt = Carbon::parse($date->toDateString().' '.$time);
                $endsAt = $startsAt->copy()->addMinutes((int) $movie->duration);
                if ($startsAt->isPast()) {
                    if ($data['auto_allocate']) {
                        $skipped[] = $this->skippedSlot($startsAt, $endsAt, 'Khung giờ đã qua.');
                    } else {
                        foreach ($compatibleRooms as $room) {
                            $skipped[] = [
                                'room_id' => $room->id,
                                'room_name' => $room->name,
                                'cinema_name' => $room->cinema->name,
                                'starts_at' => $startsAt->toDateTimeString(),
                                'ends_at' => $endsAt->toDateTimeString(),
                                'skip_reason' => 'Khung giờ đã qua.',
                            ];
                        }
                    }

                    continue;
                }

                $assigned = false;
                foreach ($compatibleRooms as $room) {
                    $candidate = [
                        'room_id' => $room->id,
                        'room_name' => $room->name,
                        'cinema_name' => $room->cinema->name,
                        'starts_at' => $startsAt->toDateTimeString(),
                        'ends_at' => $endsAt->toDateTimeString(),
                    ];

                    if ($this->hasConflict($candidate, $accepted, $existingShowtimes)) {
                        if (! $data['auto_allocate']) {
                            $skipped[] = [...$candidate, 'skip_reason' => 'Trùng lịch hoặc chưa đủ thời gian nghỉ.'];
                        }

                        continue;
                    }

                    $accepted[] = $candidate;
                    $assigned = true;
                    if ($data['auto_allocate']) {
                        break;
                    }
                }

                if ($data['auto_allocate'] && ! $assigned) {
                    $skipped[] = $this->skippedSlot($startsAt, $endsAt, 'Không còn phòng tương thích đang trống.');
                }
            }
        }

        return [
            'accepted' => $accepted,
            'skipped' => $skipped,
            'count' => count($accepted),
        ];
    }

    private function hasConflict(array $candidate, array $accepted, Collection $existingShowtimes): bool
    {
        $conflictsWithDatabase = $existingShowtimes->contains(fn (Showtime $existing) => (int) $existing->room_id === (int) $candidate['room_id']
            && ShowtimeRules::overlaps(
                Carbon::parse($candidate['starts_at']),
                Carbon::parse($candidate['ends_at']),
                Carbon::parse($existing->starts_at),
                Carbon::parse($existing->ends_at),
                self::CLEANUP_MINUTES
            ));

        if ($conflictsWithDatabase) {
            return true;
        }

        return collect($accepted)->contains(fn (array $existing) => $existing['room_id'] === $candidate['room_id']
            && ShowtimeRules::overlaps(
                Carbon::parse($candidate['starts_at']),
                Carbon::parse($candidate['ends_at']),
                Carbon::parse($existing['starts_at']),
                Carbon::parse($existing['ends_at']),
                self::CLEANUP_MINUTES
            ));
    }

    private function skippedSlot(Carbon $startsAt, Carbon $endsAt, string $reason): array
    {
        return [
            'room_id' => null,
            'room_name' => '—',
            'cinema_name' => '—',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'skip_reason' => $reason,
        ];
    }
}
