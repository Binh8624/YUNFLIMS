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

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::with(['cinema', 'format'])->withCount('seats')
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

        $cinemas = Cinema::orderBy('name')->get();

        return view('admin.rooms.index', compact('rooms', 'cinemas'));
    }

    public function create()
    {
        return view('admin.rooms.form', [
            'room' => new Room(['total_seats' => 60, 'is_active' => true]),
            'cinemas' => Cinema::orderBy('name')->get(),
            'formats' => Format::whereIn('slug', ['2d', '3d', 'imax'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRoom($request);
        $format = $this->activeCinemaFormat((int) $data['format_id']);

        Room::create($data)->formats()->sync([$format->id]);

        return redirect()->route('admin.rooms.index')->with('success', 'Đã thêm phòng chiếu.');
    }

    public function edit(Room $room)
    {
        return view('admin.rooms.form', [
            'room' => $room->load('format'),
            'cinemas' => Cinema::orderBy('name')->get(),
            'formats' => Format::whereIn('slug', ['2d', '3d', 'imax'])->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $data = $this->validateRoom($request, $room);
        $format = $this->activeCinemaFormat((int) $data['format_id']);

        DB::transaction(function () use ($room, $data, $format) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($lockedRoom->seats()->exists() && (int) $data['total_seats'] < $lockedRoom->seats()->count()) {
                throw ValidationException::withMessages([
                    'total_seats' => 'Sức chứa không thể thấp hơn số ghế đã tạo trong sơ đồ.',
                ]);
            }

            if ((int) $lockedRoom->format_id !== (int) $format->id
                && $lockedRoom->showtimes()->where('format_id', '!=', $format->id)->exists()) {
                throw ValidationException::withMessages([
                    'format_id' => 'Không thể đổi định dạng vì phòng có suất chiếu hoặc vé gắn với định dạng hiện tại.',
                ]);
            }

            $lockedRoom->update($data);
            $lockedRoom->formats()->sync([$format->id]);
        });

        return redirect()->route('admin.rooms.index')->with('success', 'Đã cập nhật phòng chiếu.');
    }

    public function destroy(Room $room)
    {
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

        return redirect()->route('admin.rooms.index')->with('success', 'Đã xóa phòng chiếu.');
    }

    private function validateRoom(Request $request, ?Room $room = null): array
    {
        return $request->validate([
            'cinema_id' => ['required', 'integer', 'exists:cinemas,id'],
            'room_code' => ['required', 'string', 'max:30', Rule::unique('rooms', 'room_code')->ignore($room?->id)],
            'name' => ['required', 'string', 'max:100'],
            'format_id' => ['required', 'integer', 'exists:formats,id'],
            'total_seats' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function activeCinemaFormat(int $formatId): Format
    {
        $format = Format::whereKey($formatId)
            ->whereIn('slug', ['2d', '3d', 'imax'])
            ->where('is_active', true)
            ->first();

        if (! $format) {
            throw ValidationException::withMessages([
                'format_id' => 'Chọn một định dạng hợp lệ: 2D, 3D hoặc IMAX.',
            ]);
        }

        return $format;
    }
}
