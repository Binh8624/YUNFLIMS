<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('showtimes', function (Blueprint $table) {
        $table->id();

        $table->foreignId('movie_id')
            ->constrained('movies')
            ->restrictOnDelete();

        $table->foreignId('room_id')
            ->constrained('rooms')
            ->restrictOnDelete();

        $table->dateTime('starts_at');
        $table->dateTime('ends_at');

        $table->decimal('price', 10, 2);

        $table->enum('status', ['scheduled', 'cancelled'])
            ->default('scheduled');

        $table->timestamps();

        $table->index(['room_id', 'starts_at']);
        $table->index(['movie_id', 'starts_at']);
    });
}

public function down(): void
{
    Schema::dropIfExists('showtimes');
}
};
