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
    Schema::create('cinemas', function (Blueprint $table) {
        $table->id();
        $table->string('name', 150);
        $table->string('address', 255);
        $table->string('city', 100)->default('Hà Nội');
        $table->string('phone', 30)->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('cinemas');
}
};
