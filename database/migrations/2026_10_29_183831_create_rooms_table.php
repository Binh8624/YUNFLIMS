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
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();

        $table->foreignId('cinema_id')
            ->constrained('cinemas')
            ->cascadeOnDelete();

        $table->string('name', 100);
        $table->unsignedSmallInteger('total_seats')->default(60);
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['cinema_id', 'name']);
    });
}

public function down(): void
{
    Schema::dropIfExists('rooms');
}
};
