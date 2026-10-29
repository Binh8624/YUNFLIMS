<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeatController extends Controller
{
    public function index(Room $room)
    {
        $room->load('cinema');
        $seats = $room->seats()->orderBy('row_number')->orderBy('seat_number')->get();
        $rows = $seats->groupBy('row_number');

        return view('admin.seats.index', compact('room', 'rows'));
    }

    public function store(Request $request, Room $room)
    {
        $data = $this->validateSeat($request);

        DB::transaction(function () use ($room, $data) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            if ($lockedRoom->seats()->count() >= $lockedRoom->total_seats) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Sơ đồ đã đạt sức chứa của phòng.',
                ]);
            }

            $seatCode = $this->seatCode((int) $data['row_number'], (int) $data['seat_number']);
            if ($lockedRoom->seats()->where('seat_code', $seatCode)->exists()) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Vị trí ghế này đã tồn tại trong phòng.',
                ]);
            }

            $lockedRoom->seats()->create([
                ...$data,
                'seat_code' => $seatCode,
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã thêm ghế.');
    }

    public function storeRow(Request $request, Room $room)
    {
        $data = $request->validate([
            'row_number' => ['required', 'integer', 'min:1', 'max:26'],
            'seat_count' => ['required', 'integer', 'min:1', 'max:999'],
            'seat_type' => ['required', 'in:standard,vip,couple'],
        ]);

        DB::transaction(function () use ($room, $data) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $row = (int) $data['row_number'];
            $seatCount = (int) $data['seat_count'];

            if ($lockedRoom->seats()->where('row_number', $row)->exists()) {
                throw ValidationException::withMessages([
                    'row_number' => 'Hàng này đã tồn tại trong sơ đồ.',
                ]);
            }
            if ($lockedRoom->seats()->count() + $seatCount > $lockedRoom->total_seats) {
                throw ValidationException::withMessages([
                    'seat_count' => 'Số ghế của hàng vượt quá sức chứa còn lại của phòng.',
                ]);
            }

            for ($number = 1; $number <= $seatCount; $number++) {
                $lockedRoom->seats()->create([
                    'row_number' => $row,
                    'seat_number' => $number,
                    'seat_code' => $this->seatCode($row, $number),
                    'seat_type' => $data['seat_type'],
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã tạo hàng ghế và tự động đánh số.');
    }

    public function destroyRow(Room $room, int $rowNumber)
    {
        DB::transaction(function () use ($room, $rowNumber) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $seats = $lockedRoom->seats()->where('row_number', $rowNumber)->lockForUpdate()->get();

            if ($seats->isEmpty()) {
                throw ValidationException::withMessages(['row_number' => 'Không tìm thấy hàng ghế.']);
            }
            if ($seats->contains(fn (Seat $seat) => $seat->bookingSeats()->exists())) {
                throw ValidationException::withMessages([
                    'row_number' => 'Hàng có ghế thuộc lịch sử đặt vé và không thể xóa.',
                ]);
            }

            Seat::whereIn('id', $seats->modelKeys())->delete();
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã xóa hàng ghế.');
    }

    public function storeColumn(Request $request, Room $room)
    {
        $data = $request->validate([
            'seat_number' => ['required', 'integer', 'min:1', 'max:999'],
            'seat_type' => ['required', 'in:standard,vip,couple'],
        ]);

        DB::transaction(function () use ($room, $data) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $rows = $lockedRoom->seats()->select('row_number')->distinct()->orderBy('row_number')->pluck('row_number');
            $seatNumber = (int) $data['seat_number'];

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Tạo ít nhất một hàng ghế trước khi thêm cột.',
                ]);
            }
            if ($lockedRoom->seats()->count() + $rows->count() > $lockedRoom->total_seats) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Cột mới vượt quá sức chứa còn lại của phòng.',
                ]);
            }
            if ($lockedRoom->seats()->where('seat_number', $seatNumber)->exists()) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Số ghế này đã tồn tại ở ít nhất một hàng.',
                ]);
            }

            foreach ($rows as $row) {
                $lockedRoom->seats()->create([
                    'row_number' => $row,
                    'seat_number' => $seatNumber,
                    'seat_code' => $this->seatCode((int) $row, $seatNumber),
                    'seat_type' => $data['seat_type'],
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã thêm cột ghế và tự động đánh số.');
    }

    public function destroyColumn(Room $room, int $seatNumber)
    {
        DB::transaction(function () use ($room, $seatNumber) {
            $lockedRoom = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $seats = $lockedRoom->seats()->where('seat_number', $seatNumber)->lockForUpdate()->get();

            if ($seats->isEmpty()) {
                throw ValidationException::withMessages(['seat_number' => 'Không tìm thấy cột ghế.']);
            }
            if ($seats->contains(fn (Seat $seat) => $seat->bookingSeats()->exists())) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Cột có ghế thuộc lịch sử đặt vé và không thể xóa.',
                ]);
            }

            Seat::whereIn('id', $seats->modelKeys())->delete();
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã xóa cột ghế.');
    }

    public function update(Request $request, Room $room, Seat $seat)
    {
        $data = $this->validateSeat($request);
        DB::transaction(function () use ($room, $seat, $data) {
            $lockedSeat = Seat::whereKey($seat->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedSeat->room_id === $room->id, 404);

            if (
                $lockedSeat->bookingSeats()->exists()
                && (
                    (int) $data['row_number'] !== $lockedSeat->row_number
                    || (int) $data['seat_number'] !== $lockedSeat->seat_number
                    || $data['seat_type'] !== $lockedSeat->seat_type
                )
            ) {
                throw ValidationException::withMessages([
                    'seat' => 'Không thể đổi vị trí hoặc loại ghế đã xuất hiện trong đơn đặt vé; chỉ được khóa hoặc mở khóa ghế.',
                ]);
            }

            $seatCode = $this->seatCode((int) $data['row_number'], (int) $data['seat_number']);
            if ($room->seats()->where('seat_code', $seatCode)->where('id', '!=', $lockedSeat->id)->exists()) {
                throw ValidationException::withMessages([
                    'seat_number' => 'Vị trí ghế này đã tồn tại trong phòng.',
                ]);
            }

            $lockedSeat->update([...$data, 'seat_code' => $seatCode]);
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã cập nhật ghế.');
    }

    public function destroy(Room $room, Seat $seat)
    {
        DB::transaction(function () use ($room, $seat) {
            $lockedSeat = Seat::whereKey($seat->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedSeat->room_id === $room->id, 404);

            if ($lockedSeat->bookingSeats()->exists()) {
                throw ValidationException::withMessages([
                    'seat' => 'Ghế đã có trong lịch sử đặt vé và không thể xóa. Hãy vô hiệu hóa ghế.',
                ]);
            }

            $lockedSeat->delete();
        });

        return redirect()->route('admin.rooms.seats.index', $room)->with('success', 'Đã xóa ghế.');
    }

    private function validateSeat(Request $request): array
    {
        return $request->validate([
            'row_number' => ['required', 'integer', 'min:1', 'max:26'],
            'seat_number' => ['required', 'integer', 'min:1', 'max:999'],
            'seat_type' => ['required', 'in:standard,vip,couple'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function seatCode(int $rowNumber, int $seatNumber): string
    {
        return chr(64 + $rowNumber).$seatNumber;
    }
}
