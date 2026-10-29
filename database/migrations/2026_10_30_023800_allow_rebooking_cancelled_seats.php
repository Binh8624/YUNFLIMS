<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->index('showtime_id');
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropUnique(['showtime_id', 'seat_id']);
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->index(['showtime_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        $duplicateSeatAssignments = DB::table('booking_seats')
            ->select('showtime_id', 'seat_id')
            ->groupBy('showtime_id', 'seat_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateSeatAssignments) {
            throw new RuntimeException(
                'Cannot restore the unique seat constraint while duplicate historical seat assignments exist.'
            );
        }

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->unique(['showtime_id', 'seat_id']);
        });

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropIndex(['showtime_id', 'seat_id']);
            $table->dropIndex(['showtime_id']);
        });
    }
};
