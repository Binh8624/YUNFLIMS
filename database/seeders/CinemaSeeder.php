<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Format;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $cinema = Cinema::firstOrCreate(
            ['name' => 'YUNFLIMS Hà Nội'],
            [
                'address' => 'Hà Nội, Việt Nam',
                'city' => 'Hà Nội',
                'phone' => null,
                'is_active' => true,
            ]
        );

        $room = Room::firstOrCreate(
            [
                'cinema_id' => $cinema->id,
                'name' => 'Phòng 01',
            ],
            [
                'total_seats' => 60,
                'is_active' => true,
            ]
        );
        if ($room->room_code === null) {
            $room->update(['room_code' => 'R'.$room->id]);
        }

        if (Schema::hasTable('room_format')) {
            $defaultFormat = Format::where('slug', '2d')->first();
            if ($defaultFormat) {
                $room->formats()->syncWithoutDetaching([$defaultFormat->id]);
            }
        }

        // Tạo 60 ghế: 6 hàng, mỗi hàng 10 ghế.
        for ($row = 0; $row < 6; $row++) {
            $rowLetter = chr(65 + $row);

            for ($number = 1; $number <= 10; $number++) {
                $seatCode = $rowLetter . $number;

                Seat::firstOrCreate(
                    [
                        'room_id' => $room->id,
                        'seat_code' => $seatCode,
                    ],
                    [
                        'row_number' => $row + 1,
                        'seat_number' => $number,
                        'seat_type' => $row >= 4
                            ? 'vip'
                            : 'standard',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}