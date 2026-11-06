<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Format;
use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ShowtimeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:scheduled,cancelled'],
        ]);

        $showtimes = Showtime::with(['movie', 'room.cinema', 'format'])
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search) {
                    $query->whereHas('movie', fn ($movie) => $movie->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('room', fn ($room) => $room->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('starts_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.showtimes.index', compact('showtimes'));
    }

    public function create()
    {
        $movies = Movie::where('status', '!=', 'inactive')->orderBy('title')->get();

        $rooms = Room::with('cinema')
            ->where('is_active', true)
            ->whereHas('cinema', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
        $formats = Format::where('is_active', true)->orderBy('name')->get();

        return view('admin.showtimes.create', compact('movies', 'rooms', 'formats'));
    }

    public function store(Request $request)
    {
        $data = $this->validateShowtime($request);
        DB::transaction(function () use ($data) {
            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $movie = Movie::whereKey($data['movie_id'])->lockForUpdate()->firstOrFail();
            $this->ensureFormatCompatibility($data, $movie, $room);
            $this->ensureRoomIsAvailable($data['room_id'], $data['starts_at'], $data['ends_at']);
            Showtime::create($data);
        });

        return redirect()
            ->route('admin.showtimes.index')
            ->with('success', 'Thêm suất chiếu thành công!');
    }

    public function edit(Showtime $showtime)
    {
        $movies = Movie::where('status', '!=', 'inactive')->orderBy('title')->get();

        $rooms = Room::with('cinema')->orderBy('name')->get();
        $formats = Format::where('is_active', true)->orderBy('name')->get();

        return view(
            'admin.showtimes.edit',
            compact('showtime', 'movies', 'rooms', 'formats')
        );
    }

    public function update(Request $request, Showtime $showtime)
    {
        $data = $this->validateShowtime($request);
        DB::transaction(function () use ($data, $showtime) {
            $lockedShowtime = Showtime::whereKey($showtime->id)->lockForUpdate()->firstOrFail();
            if ($lockedShowtime->bookings()->exists()) {
                throw ValidationException::withMessages([
                    'showtime' => 'Suất chiếu đã có đơn đặt vé. Không thể thay đổi phim, phòng hoặc thời gian để bảo toàn lịch sử.',
                ]);
            }

            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $movie = Movie::whereKey($data['movie_id'])->lockForUpdate()->firstOrFail();
            $this->ensureFormatCompatibility($data, $movie, $room);
            $this->ensureRoomIsAvailable($data['room_id'], $data['starts_at'], $data['ends_at'], $lockedShowtime->id);
            $lockedShowtime->update($data);
        });

        return redirect()
            ->route('admin.showtimes.index')
            ->with('success', 'Cập nhật suất chiếu thành công!');
    }

    public function destroy(Showtime $showtime)
    {
        $action = DB::transaction(function () use ($showtime) {
            $lockedShowtime = Showtime::whereKey($showtime->id)->lockForUpdate()->firstOrFail();
            if ($lockedShowtime->bookings()->where('status', 'confirmed')->exists()) {
                throw ValidationException::withMessages([
                    'showtime' => 'Suất chiếu có vé đã xác nhận. Hãy xử lý hoàn tiền/thông báo khách trước khi hủy.',
                ]);
            }

            if (! $lockedShowtime->bookings()->exists()) {
                $lockedShowtime->delete();

                return 'deleted';
            }

            $lockedShowtime->bookings()
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);
            $lockedShowtime->update(['status' => 'cancelled']);

            return 'cancelled';
        });

        return redirect()
            ->route('admin.showtimes.index')
            ->with(
                'success',
                $action === 'deleted'
                    ? 'Đã xóa suất chiếu chưa phát sinh đơn vé.'
                    : 'Đã hủy suất chiếu và các đơn đang chờ thanh toán.'
            );
    }

    private function validateShowtime(Request $request): array
    {
        if ($request->filled('starts_on') && $request->filled('starts_time')) {
            $request->merge([
                'starts_at' => $request->input('starts_on') . ' ' . $request->input('starts_time'),
            ]);
        }

        $data = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'room_id' => 'required|integer|exists:rooms,id',
            'starts_on' => 'required|date_format:Y-m-d',
            'starts_time' => 'required|date_format:H:i',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'status' => 'required|in:scheduled,cancelled',
            'format_id' => 'required|integer|exists:formats,id,is_active,1',
        ], [
            'starts_at.after' => 'Thời gian bắt đầu phải sau thời điểm hiện tại. Vui lòng chọn giờ chiếu sắp tới.',
        ], [
            'starts_at' => 'thời gian bắt đầu',
        ]);
        unset($data['starts_on'], $data['starts_time']);

        $movie = Movie::findOrFail($data['movie_id']);
        $room = Room::with(['cinema', 'formats'])->findOrFail($data['room_id']);
        $format = Format::findOrFail($data['format_id']);

        if (! $room->is_active || ! $room->cinema?->is_active) {
            throw ValidationException::withMessages([
                'room_id' => 'Phòng chiếu hoặc rạp hiện không hoạt động.',
            ]);
        }

        if (
            $movie->status === 'inactive'
            || ! $format->is_active
            ||
            ! $movie->formats()->whereKey($format->id)->exists()
            || ! $room->formats->contains('id', $format->id)
        ) {
            throw ValidationException::withMessages([
                'format_id' => 'Định dạng phim không tương thích với phim hoặc phòng chiếu.',
            ]);
        }

        $data['ends_at'] = Carbon::parse($data['starts_at'])
            ->addMinutes((int) $movie->duration)
            ->toDateTimeString();

        return $data;
    }

    private function ensureRoomIsAvailable(
        int|string $roomId,
        string $startsAt,
        string $endsAt,
        ?int $ignoreId = null
    ): void {
        $conflict = Showtime::where('room_id', $roomId)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', Carbon::parse($endsAt)->addMinutes(15))
            ->where('ends_at', '>', Carbon::parse($startsAt)->subMinutes(15))
            ->when(
                $ignoreId,
                fn ($query) => $query->where('id', '!=', $ignoreId)
            )
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' =>
                    'Phòng này đã có suất chiếu trùng thời gian. Vui lòng chọn giờ khác.',
            ]);
        }
    }

    private function ensureFormatCompatibility(array $data, Movie $movie, Room $room): void
    {
        $movie->load('formats');
        $room->load(['cinema', 'formats']);
        $format = Format::whereKey($data['format_id'])->firstOrFail();

        if (
            $movie->status === 'inactive'
            || ! $format->is_active
            || ! $room->is_active
            || ! $room->cinema?->is_active
            || ! $movie->formats->contains('id', $format->id)
            || ! $room->formats->contains('id', $format->id)
        ) {
            throw ValidationException::withMessages([
                'format_id' => 'Định dạng phim không tương thích với phim hoặc phòng chiếu.',
            ]);
        }
    }
}