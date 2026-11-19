<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung dữ liệu định dạng thiếu vào bảng pivot movie_format và room_format.
     * Hoàn toàn không sửa cấu trúc bảng (DDL), bảo toàn nguyên vẹn format_id cũ.
     */
    public function up(): void
    {
        // 1. Đồng bộ các định dạng từ format_id đơn hiện hành vào pivot (nếu chưa có)
        if (Schema::hasColumn('movies', 'format_id') && Schema::hasTable('movie_format')) {
            DB::table('movies')
                ->whereNotNull('format_id')
                ->orderBy('id')
                ->chunkById(200, function ($movies) {
                    $records = [];
                    foreach ($movies as $movie) {
                        $records[] = [
                            'movie_id' => $movie->id,
                            'format_id' => $movie->format_id,
                        ];
                    }
                    DB::table('movie_format')->insertOrIgnore($records);
                });
        }

        if (Schema::hasColumn('rooms', 'format_id') && Schema::hasTable('room_format')) {
            DB::table('rooms')
                ->whereNotNull('format_id')
                ->orderBy('id')
                ->chunkById(200, function ($rooms) {
                    $records = [];
                    foreach ($rooms as $room) {
                        $records[] = [
                            'room_id' => $room->id,
                            'format_id' => $room->format_id,
                        ];
                    }
                    DB::table('room_format')->insertOrIgnore($records);
                });
        }

        // 2. Phục hồi các định dạng đã từng chiếu trong lịch sử showtimes vào pivot
        if (Schema::hasTable('showtimes') && Schema::hasTable('movie_format')) {
            $historicalMovieFormats = DB::table('showtimes')
                ->whereNotNull('format_id')
                ->select('movie_id', 'format_id')
                ->distinct()
                ->get();

            foreach ($historicalMovieFormats->chunk(200) as $chunk) {
                $data = $chunk->map(fn ($row) => [
                    'movie_id' => $row->movie_id,
                    'format_id' => $row->format_id,
                ])->all();

                DB::table('movie_format')->insertOrIgnore($data);
            }
        }

        if (Schema::hasTable('showtimes') && Schema::hasTable('room_format')) {
            $historicalRoomFormats = DB::table('showtimes')
                ->whereNotNull('format_id')
                ->select('room_id', 'format_id')
                ->distinct()
                ->get();

            foreach ($historicalRoomFormats->chunk(200) as $chunk) {
                $data = $chunk->map(fn ($row) => [
                    'room_id' => $row->room_id,
                    'format_id' => $row->format_id,
                ])->all();

                DB::table('room_format')->insertOrIgnore($data);
            }
        }
    }

    /**
     * Rollback an toàn: Không xóa dữ liệu trong pivot tables để tránh mất định dạng.
     */
    public function down(): void
    {
        // Safe no-op: Không xóa dữ liệu pivot để bảo toàn toàn vẹn dữ liệu
    }
};

