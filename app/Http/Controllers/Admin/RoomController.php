<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manage-cinema');
        $rooms = Room::with(['cinema', 'format', 'formats'])->withCount('seats')
            ->when(! $request->user()->canManageCinema(null), fn ($query) => $query->where('cinema_id', $request->user()->cinema_id))
            ->when($request->filled('cinema_id'), fn ($query) => $query->where('cinema_id', $request->integer('cinema_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('room_code', 'like', "%{$search}%")
                        ->orWhereHas('cinema', fn ($cinema) => $cinema->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('cinema_id')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $cinemas = $this->cinemasForRequest();

        return view('admin.rooms.index', compact('rooms', 'cinemas'));
    }

    public function create()
    {
        $cinemas = $this->cinemasForRequest();

        return view('admin.rooms.form', [
            'room' => new Room(['total_seats' => 60, 'is_active' => true]),
            'cinemas' => $cinemas,
            'formats' => $this->availableFormats(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRoom($request);
        abort_unless($request->user()->canManageCinema((int) $data['cinema_id']), 403);

        $formats = $this->activeCinemaFormats($data['format_ids']);
        $formatIds = $formats->pluck('id')->map(fn ($id) => (int) $id)->all();

        $data['format_id'] = $formatIds[0];
        unset($data['format_ids']);

        DB::transaction(function () use ($data, $formatIds) {
            $room = Room::create($data);
            $room->formats()->sync($formatIds);
        });

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Đã thêm phòng chiếu.');
    }

    public function edit(Room $room)
    {
        abort_unless($this->canManageRoom($room), 403);

        return view('admin.rooms.form', [
            'room' => $room->load(['format', 'formats']),
            'cinemas' => $this->cinemasForRequest(),
            'formats' => $this->availableFormats(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        abort_unless($this->canManageRoom($room), 403);
        $data = $this->validateRoom($request, $room);

        $formats = $this->activeCinemaFormats($data['format_ids']);
        $formatIds = $formats->pluck('id')->map(fn ($id) => (int) $id)->all();

        $data['format_id'] = $formatIds[0];
        unset($data['format_ids']);

        DB::transaction(function () use ($room, $data, $formatIds) {
            $lockedRoom = Room::whereKey($room->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Không cho tắt phòng nếu còn suất tương lai đã bán vé.
            if ($lockedRoom->is_active && ! $data['is_active']) {
                $hasPaidFutureBookings = $lockedRoom->showtimes()
                    ->where('starts_at', '>', now())
                    ->whereHas('bookings', function ($query) {
                        $query->where('bookings.status', 'confirmed')
                            ->where('bookings.payment_status', 'paid');
                    })
                    ->exists();

                if ($hasPaidFutureBookings) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Không thể vô hiệu hóa phòng vì vẫn còn suất chiếu tương lai đã có khách thanh toán. Hãy xử lý các vé đã bán trước.',
                    ]);
                }
            }

            $seatCount = $lockedRoom->seats()->count();

            if ((int) $data['total_seats'] < $seatCount) {
                throw ValidationException::withMessages([
                    'total_seats' => 'Sức chứa không thể thấp hơn số ghế đã tạo trong sơ đồ.',
                ]);
            }

            // Không cho bỏ định dạng đang được suất chiếu sử dụng.
            $hasIncompatibleShowtime = $lockedRoom->showtimes()
                ->whereNotIn('format_id', $formatIds)
                ->exists();

            if ($hasIncompatibleShowtime) {
                throw ValidationException::withMessages([
                    'format_ids' => 'Không thể bỏ định dạng đang được suất chiếu sử dụng. Hãy xử lý các suất chiếu liên quan trước.',
                ]);
            }

            $lockedRoom->update($data);
            $lockedRoom->formats()->sync($formatIds);
        });

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Đã cập nhật phòng chiếu.');
    }

    public function destroy(Room $room)
    {
        abort_unless($this->canManageRoom($room), 403);
        if (
            $room->showtimes()->exists()
            || $room->seats()->whereHas('bookingSeats')->exists()
            || $room->showtimes()->whereHas('bookings')->exists()
        ) {
            throw ValidationException::withMessages([
                'room' => 'Phòng đã có lịch chiếu hoặc ghế phát sinh đơn vé. Hãy vô hiệu hóa để giữ lịch sử.',
            ]);
        }

        $room->delete();

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Đã xóa phòng chiếu.');
    }

    private function validateRoom(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'cinema_id' => ['required', 'integer', 'exists:cinemas,id'],
            'room_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('rooms', 'room_code')->ignore($room?->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'format_ids' => ['required', 'array', 'min:1'],
            'format_ids.*' => ['required', 'integer', 'distinct', 'exists:formats,id'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ], [
            'format_ids.required' => 'Vui lòng chọn ít nhất một định dạng phòng chiếu.',
            'format_ids.min' => 'Phòng chiếu phải có ít nhất một định dạng.',
            'format_ids.*.distinct' => 'Không được chọn trùng định dạng.',
            'format_ids.*.exists' => 'Định dạng phòng chiếu không hợp lệ.',
        ]);
    }

    private function canManageRoom(Room $room): bool
    {
        return request()->user()->canManageCinema((int) $room->cinema_id);
    }

    private function cinemasForRequest()
    {
        $query = Cinema::query()->orderBy('name');
        if (! request()->user()->canManageCinema(null)) {
            $query->whereKey(request()->user()->cinema_id);
        }

        return $query->get();
    }

    private function availableFormats()
    {
        return Format::whereIn('slug', ['2d', '3d', 'imax'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function activeCinemaFormats(array $formatIds)
    {
        $formatIds = array_values(array_unique(array_map('intval', $formatIds)));

        $formats = Format::whereIn('id', $formatIds)
            ->whereIn('slug', ['2d', '3d', 'imax'])
            ->where('is_active', true)
            ->get();

        if ($formats->count() !== count($formatIds)) {
            throw ValidationException::withMessages([
                'format_ids' => 'Có định dạng không hợp lệ hoặc đã ngừng hoạt động. Vui lòng kiểm tra lại.',
            ]);
        }

        return $formats;
    }
}
