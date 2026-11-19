<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('trailer_url', 2048)->nullable();
            $table->string('age_rating', 10)->default('P');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_code', 30)->nullable()->unique();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('hold_expires_at')->nullable()->index();
        });

        Schema::create('formats', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('movie_format', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('format_id')->constrained()->cascadeOnDelete();
            $table->primary(['movie_id', 'format_id']);
        });

        Schema::create('room_format', function (Blueprint $table) {
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('format_id')->constrained()->cascadeOnDelete();
            $table->primary(['room_id', 'format_id']);
        });

        Schema::table('showtimes', function (Blueprint $table) {
            $table->foreignId('format_id')->nullable()->after('room_id')
                ->constrained('formats')->nullOnDelete();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['movie_id', 'user_id']);
            $table->index(['movie_id', 'created_at']);
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('seat_type', 20)->nullable();
            $table->foreignId('format_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('day_type', ['any', 'weekday', 'weekend'])->default('any');
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->enum('amount_type', ['surcharge', 'fixed'])->default('surcharge');
            $table->decimal('amount', 10, 2);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'priority']);
        });

        $now = now();
        DB::table('formats')->insert([
            ['name' => '2D', 'slug' => '2d', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => '3D', 'slug' => '3d', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'IMAX', 'slug' => 'imax', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $defaultFormatId = DB::table('formats')->where('slug', '2d')->value('id');

        DB::table('movies')->select('id')->orderBy('id')->chunkById(500, function ($movies) use ($defaultFormatId) {
            foreach ($movies as $movie) {
                DB::table('movie_format')->insertOrIgnore([
                    'movie_id' => $movie->id,
                    'format_id' => $defaultFormatId,
                ]);
            }
        });

        DB::table('rooms')->select('id')->orderBy('id')->chunkById(500, function ($rooms) use ($defaultFormatId) {
            foreach ($rooms as $room) {
                DB::table('room_format')->insertOrIgnore([
                    'room_id' => $room->id,
                    'format_id' => $defaultFormatId,
                ]);
                DB::table('rooms')->where('id', $room->id)->update([
                    'room_code' => 'R' . $room->id,
                ]);
            }
        });

        DB::table('showtimes')->update(['format_id' => $defaultFormatId]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('reviews');
        Schema::table('showtimes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('format_id');
        });
        Schema::dropIfExists('room_format');
        Schema::dropIfExists('movie_format');
        Schema::dropIfExists('formats');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['hold_expires_at']);
            $table->dropColumn('hold_expires_at');
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['room_code']);
            $table->dropColumn('room_code');
        });
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['trailer_url', 'age_rating']);
        });
    }
};
