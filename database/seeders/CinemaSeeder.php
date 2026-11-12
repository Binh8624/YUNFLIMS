<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Room;
use App\Models\Seat;
use App\Models\Format;
use Illuminate\Database\Seeder;

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

        $defaultFormat = Format::where('slug', '2d')->firstOrFail();
        $room = Room::firstOrCreate(
            [
                'cinema_id' => $cinema->id,
                'name' => 'Phòng 01',
            ],
            [
                'format_id' => $defaultFormat->id,
                'total_seats' => 60,
                'is_active' => true,
            ]
        );
        if ($room->room_code === null) {
            $room->update(['room_code' => 'R'.$room->id]);
        }

        $room->formats()->sync([(int) $room->format_id]);

        $this->createSeatMap($room);

        foreach ([
            ['name' => 'Phòng 02', 'format' => '2d'],
            ['name' => 'Phòng 03', 'format' => '2d'],
            ['name' => 'Phòng 04', 'format' => '3d'],
            ['name' => 'Phòng 05', 'format' => '3d'],
            ['name' => 'Phòng 06', 'format' => 'imax'],
        ] as $roomData) {
            $format = Format::where('slug', $roomData['format'])->firstOrFail();
            $newRoom = Room::firstOrCreate(
                [
                    'cinema_id' => $cinema->id,
                    'name' => $roomData['name'],
                ],
                [
                    'format_id' => $format->id,
                    'total_seats' => 60,
                    'is_active' => true,
                ]
            );

            if ($newRoom->room_code === null) {
                $newRoom->update(['room_code' => 'R'.$newRoom->id]);
            }

            $newRoom->formats()->sync([(int) $newRoom->format_id]);
            if ($newRoom->wasRecentlyCreated) {
                $this->createSeatMap($newRoom);
            }
        }
    }

    private function createSeatMap(Room $room): void
    {
        if ($room->seats()->exists()) {
            return;
        }

        for ($row = 0; $row < 6; $row++) {
            $rowLetter = chr(65 + $row);

            for ($number = 1; $number <= 10; $number++) {
                $seatCode = $rowLetter . $number;

                Seat::firstOrCreate(
                    ['room_id' => $room->id, 'seat_code' => $seatCode],
                    [
                        'row_number' => $row + 1,
                        'seat_number' => $number,
                        'seat_type' => $row >= 4 ? 'vip' : 'standard',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}