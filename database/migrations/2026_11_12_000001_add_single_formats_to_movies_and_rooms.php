<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (['2d' => '2D', '3d' => '3D', 'imax' => 'IMAX'] as $slug => $name) {
            DB::table('formats')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
        DB::table('formats')->whereNotIn('slug', ['2d', '3d', 'imax'])->update([
            'is_active' => false,
            'updated_at' => $now,
        ]);

        $formatIds = DB::table('formats')
            ->whereIn('slug', ['2d', '3d', 'imax'])
            ->pluck('id', 'slug');
        $defaultFormatId = (int) $formatIds['2d'];
        $allowedFormatIds = $formatIds->values()->all();

        Schema::table('movies', function (Blueprint $table) use ($defaultFormatId) {
            $table->foreignId('format_id')->default($defaultFormatId)->constrained('formats')->restrictOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table) use ($defaultFormatId) {
            $table->foreignId('format_id')->default($defaultFormatId)->constrained('formats')->restrictOnDelete();
        });

        DB::table('movies')->select('id')->orderBy('id')->chunkById(500, function ($movies) use ($defaultFormatId, $allowedFormatIds) {
            foreach ($movies as $movie) {
                $formatId = DB::table('showtimes')
                    ->where('movie_id', $movie->id)
                    ->whereIn('format_id', $allowedFormatIds)
                    ->select('format_id', DB::raw('COUNT(*) as usage_count'))
                    ->groupBy('format_id')
                    ->orderByDesc('usage_count')
                    ->orderBy('format_id')
                    ->value('format_id');

                $formatId ??= DB::table('movie_format')
                    ->where('movie_id', $movie->id)
                    ->whereIn('format_id', $allowedFormatIds)
                    ->orderBy('format_id')
                    ->value('format_id');

                $formatId = (int) ($formatId ?? $defaultFormatId);
                DB::table('movies')->where('id', $movie->id)->update(['format_id' => $formatId]);
                DB::table('movie_format')->where('movie_id', $movie->id)->delete();
                DB::table('movie_format')->insert(['movie_id' => $movie->id, 'format_id' => $formatId]);
            }
        });

        DB::table('rooms')->select('id')->orderBy('id')->chunkById(500, function ($rooms) use ($defaultFormatId, $allowedFormatIds) {
            foreach ($rooms as $room) {
                $formatId = DB::table('showtimes')
                    ->where('room_id', $room->id)
                    ->whereIn('format_id', $allowedFormatIds)
                    ->select('format_id', DB::raw('COUNT(*) as usage_count'))
                    ->groupBy('format_id')
                    ->orderByDesc('usage_count')
                    ->orderBy('format_id')
                    ->value('format_id');

                $formatId ??= DB::table('room_format')
                    ->where('room_id', $room->id)
                    ->whereIn('format_id', $allowedFormatIds)
                    ->orderBy('format_id')
                    ->value('format_id');

                $formatId = (int) ($formatId ?? $defaultFormatId);
                DB::table('rooms')->where('id', $room->id)->update(['format_id' => $formatId]);
                DB::table('room_format')->where('room_id', $room->id)->delete();
                DB::table('room_format')->insert(['room_id' => $room->id, 'format_id' => $formatId]);
            }
        });

        DB::table('showtimes')
            ->whereNull('format_id')
            ->update(['format_id' => DB::raw('(SELECT format_id FROM movies WHERE movies.id = showtimes.movie_id)')]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('format_id');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('format_id');
        });
    }
};
