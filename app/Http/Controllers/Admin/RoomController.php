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
        $rooms = Room::with('cinema')->withCount('seats')
            ->when($request->filled('cinema_id'), fn ($query) => $query->where('cinema_id', $request->integer('cinema_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
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
            'formats' => Format::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRoom($request);
        $formats = $data['formats'];
        unset($data['formats']);

        Room::create($data)->formats()->sync($formats);

        return redirect()->route('admin.rooms.index')->with('success', 'Đã thêm phòng chiếu.');
    }

    public function edit(Room $room)
    {
        return view('admin.rooms.form', [
            'room' => $room,
            'cinemas' => Cinema::orderBy('name')->get(),
            'formats' => Format::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $data = $this->validateRoom($request, $room);
        $formats = $data['formats'];
        unset($data['formats']);

        DB::transaction(function () use ($room, $data, $formats) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($lockedRoom->seats()->exists() && (int) $data['total_seats'] < $lockedRoom->seats()->count()) {
                throw ValidationException::withMessages([
                    'total_seats' => 'Sức chứa không thể thấp hơn số ghế đã tạo trong sơ đồ.',
                ]);
            }

            if (
                $lockedRoom->showtimes()
                    ->where('status', 'scheduled')
                    ->whereNotIn('format_id', $formats)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'formats' => 'Không thể bỏ định dạng đang được sử dụng bởi suất chiếu đã lên lịch.',
                ]);
            }

            $lockedRoom->update($data);
            $lockedRoom->formats()->sync($formats);
        });

        return redirect()->route('admin.rooms.index')->with('success', 'Đã cập nhật phòng chiếu.');
    }

    public function destroy(Room $room)
    {
        if ($room->showtimes()->exists() || $room->seats()->whereHas('bookingSeats')->exists()) {
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
            'total_seats' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'formats' => ['required', 'array', 'min:1'],
            'formats.*' => ['required', 'integer', 'distinct', 'exists:formats,id,is_active,1'],
        ]);
    }
}
