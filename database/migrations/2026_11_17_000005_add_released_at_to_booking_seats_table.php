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
            $table->timestamp('released_at')->nullable()->after('price');
        });

        DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->where('bookings.status', 'cancelled')
            ->whereNull('booking_seats.released_at')
            ->update(['booking_seats.released_at' => DB::raw('bookings.updated_at')]);

        $duplicates = DB::table('booking_seats as bs')
            ->join('bookings as b', 'b.id', '=', 'bs.booking_id')
            ->whereIn('b.status', ['pending', 'confirmed'])
            ->whereNull('bs.released_at')
            ->select('bs.showtime_id', 'bs.seat_id')
            ->groupBy('bs.showtime_id', 'bs.seat_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('booking_seats as bs')
                ->join('bookings as b', 'b.id', '=', 'bs.booking_id')
                ->whereIn('b.status', ['pending', 'confirmed'])
                ->whereNull('bs.released_at')
                ->where('bs.showtime_id', $duplicate->showtime_id)
                ->where('bs.seat_id', $duplicate->seat_id)
                ->select('bs.id', 'b.status', 'b.payment_status')
                ->get()
                ->sort(function ($left, $right) {
                    $rank = fn ($row) => ($row->status === 'confirmed' ? 2 : 0)
                        + ($row->payment_status === 'paid' ? 1 : 0);

                    return [$rank($right), $right->id] <=> [$rank($left), $left->id];
                });

            $keep = $rows->shift();

            if ($keep) {
                DB::table('booking_seats')
                    ->whereIn('id', $rows->pluck('id')->all())
                    ->update(['released_at' => now()]);
            }
        }

        DB::statement(
            "ALTER TABLE booking_seats ADD COLUMN active_seat_key VARCHAR(64)
             GENERATED ALWAYS AS (IF(released_at IS NULL, CONCAT(showtime_id, '-', seat_id), NULL)) STORED"
        );
        DB::statement('ALTER TABLE booking_seats ADD UNIQUE INDEX booking_seats_active_unique (active_seat_key)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE booking_seats DROP INDEX booking_seats_active_unique');
        DB::statement('ALTER TABLE booking_seats DROP COLUMN active_seat_key');

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn('released_at');
        });
    }
};
