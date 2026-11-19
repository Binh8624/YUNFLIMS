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
    Schema::create('seats', function (Blueprint $table) {
        $table->id();

        $table->foreignId('room_id')
            ->constrained('rooms')
            ->cascadeOnDelete();

        $table->string('seat_code', 10);
        $table->unsignedSmallInteger('row_number');
        $table->unsignedSmallInteger('seat_number');

        $table->enum('seat_type', ['standard', 'vip', 'couple'])
            ->default('standard');

        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['room_id', 'seat_code']);
        $table->index(['room_id', 'row_number', 'seat_number']);
    });
}

public function down(): void
{
    Schema::dropIfExists('seats');
}
};
