<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleGeneratorController extends Controller
{
    private const CLEANUP_MINUTES = 15;

    public function index()
    {
        return view('admin.showtimes.auto', [
            'movies' => Movie::where('status', '!=', 'inactive')->orderBy('title')->get(),
            'rooms' => Room::with(['cinema', 'formats'])
                ->where('is_active', true)
                ->whereHas('cinema', fn ($query) => $query->where('is_active', true))
                ->orderBy('name')
                ->get(),
            'formats' => Format::where('is_active', true)->orderBy('name')->get(),
            'preview' => session('schedule_preview'),
        ]);
    }

    public function preview(Request $request)
    {
        $data = $this->validated($request);
        $plan = $this->buildPlan($data);

        return redirect()->route('admin.showtimes.auto')
            ->withInput()
            ->with('schedule_preview', $plan);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $created = DB::transaction(function () use ($data) {
            $rooms = Room::whereIn('id', $data['room_ids'])->lockForUpdate()->get();
            Movie::whereKey($data['movie_id'])->lockForUpdate()->firstOrFail();
            $created = [];

            foreach ($this->candidates($data, $rooms) as $candidate) {
                $conflict = Showtime::where('room_id', $candidate['room_id'])
                    ->where('status', 'scheduled')
                    ->where('starts_at', '<', $candidate['blocked_until'])
                    ->where(
                        'ends_at',
                        '>',
                        Carbon::parse($candidate['starts_at'])
                            ->subMinutes(self::CLEANUP_MINUTES)
                            ->toDateTimeString()
                    )
                    ->lockForUpdate()
                    ->exists();

                if ($conflict) {
                    continue;
                }

                $created[] = Showtime::create([
                    'movie_id' => $data['movie_id'],
                    'room_id' => $candidate['room_id'],
                    'format_id' => $data['format_id'],
                    'starts_at' => $candidate['starts_at'],
                    'ends_at' => $candidate['ends_at'],
                    'price' => $data['price'],
                    'status' => 'scheduled',
                ]);
            }

            return $created;
        });

        return redirect()->route('admin.showtimes.index')
            ->with('success', count($created) . ' suất chiếu được tạo; các khung giờ xung đột đã được bỏ qua.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
            'format_id' => ['required', 'integer', 'exists:formats,id,is_active,1'],
            'room_ids' => ['required', 'array', 'min:1', 'max:10'],
            'room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'starts_on' => ['required', 'date', 'after_or_equal:today'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'times' => ['required', 'array', 'min:1', 'max:8'],
            'times.*' => ['required', 'date_format:H:i', 'distinct'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $days = Carbon::parse($data['starts_on'])->diffInDays(Carbon::parse($data['ends_on'])) + 1;
        if ($days > 31 || $days * count($data['room_ids']) * count($data['times']) > 500) {
            throw ValidationException::withMessages([
                'ends_on' => 'Tạo lịch tối đa 31 ngày và 500 khung giờ cùng lúc.',
            ]);
        }

        return $data;
    }

    private function buildPlan(array $data): array
    {
        $rooms = Room::with('formats')->whereIn('id', $data['room_ids'])->get();
        $candidates = $this->candidates($data, $rooms);
        $accepted = [];
        $skipped = [];

        foreach ($candidates as $candidate) {
            $conflict = Showtime::where('room_id', $candidate['room_id'])
                ->where('status', 'scheduled')
                ->where('starts_at', '<', $candidate['blocked_until'])
                ->where(
                    'ends_at',
                    '>',
                    Carbon::parse($candidate['starts_at'])
                        ->subMinutes(self::CLEANUP_MINUTES)
                        ->toDateTimeString()
                )
                ->exists();

            if ($conflict || collect($accepted)->contains(fn ($item) => $item['room_id'] === $candidate['room_id']
                && $item['starts_at'] < $candidate['blocked_until']
                && $item['blocked_until'] > $candidate['starts_at'])) {
                $skipped[] = $candidate;
                continue;
            }

            $accepted[] = $candidate;
        }

        return [
            'accepted' => $accepted,
            'skipped' => $skipped,
            'count' => count($accepted),
        ];
    }

    private function candidates(array $data, $rooms): array
    {
        $movie = Movie::findOrFail($data['movie_id']);
        $format = Format::findOrFail($data['format_id']);

        if (
            $movie->status === 'inactive'
            || ! $format->is_active
            || ! $movie->formats()->whereKey($format->id)->exists()
        ) {
            throw ValidationException::withMessages(['format_id' => 'Phim không hỗ trợ định dạng đã chọn.']);
        }

        $selectedRoomCount = $rooms->count();
        $rooms = $rooms->filter(function ($room) use ($format) {
            if (! $room->is_active || ! $room->cinema?->is_active) {
                return false;
            }

            if (! $room->formats->contains('id', $format->id)) {
                return false;
            }

            return true;
        });

        if ($rooms->isEmpty()) {
            throw ValidationException::withMessages(['room_ids' => 'Không có phòng đang hoạt động hỗ trợ định dạng này.']);
        }
        if ($rooms->count() !== $selectedRoomCount) {
            throw ValidationException::withMessages([
                'room_ids' => 'Một hoặc nhiều phòng đã chọn không hoạt động hoặc không hỗ trợ định dạng này.',
            ]);
        }

        $durationMinutes = (int) $movie->duration;
        $candidates = [];
        $startDate = Carbon::parse($data['starts_on'])->startOfDay();
        $endDate = Carbon::parse($data['ends_on'])->startOfDay();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            foreach ($rooms as $room) {
                foreach ($data['times'] as $time) {
                    $startsAt = Carbon::parse($date->toDateString() . ' ' . $time);
                    if ($startsAt->isPast()) {
                        continue;
                    }

                    $endsAt = $startsAt->copy()->addMinutes($durationMinutes);
                    $candidates[] = [
                        'room_id' => $room->id,
                        'room_name' => $room->name,
                        'cinema_name' => $room->cinema->name,
                        'starts_at' => $startsAt->toDateTimeString(),
                        'ends_at' => $endsAt->toDateTimeString(),
                        'blocked_until' => $endsAt->copy()->addMinutes(self::CLEANUP_MINUTES)->toDateTimeString(),
                    ];
                }
            }
        }

        usort($candidates, fn ($left, $right) => [$left['room_id'], $left['starts_at']] <=> [$right['room_id'], $right['starts_at']]);

        return $candidates;
    }
}
