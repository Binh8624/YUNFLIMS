<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('showtimes', 'vip_price')) {
            Schema::table('showtimes', function (Blueprint $table) {
                $table->decimal('vip_price', 10, 2)->nullable()->after('price');
            });
        }

        if (! Schema::hasColumn('showtimes', 'couple_price')) {
            Schema::table('showtimes', function (Blueprint $table) {
                $table->decimal('couple_price', 10, 2)->nullable()->after('vip_price');
            });
        }
    }

    public function down(): void
    {
        $existingSeatPriceMigrations = DB::table('migrations')
            ->whereIn('migration', [
                '2026_11_05_000000_add_vip_price_to_showtimes_table',
                '2026_11_05_000001_add_couple_price_to_showtimes_table',
            ])
            ->exists();

        if ($existingSeatPriceMigrations) {
            return;
        }

        Schema::table('showtimes', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['vip_price', 'couple_price'],
                fn (string $column) => Schema::hasColumn('showtimes', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
