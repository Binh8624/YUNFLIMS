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
    Schema::create('movies', function (Blueprint $table) {
        $table->id();
        $table->string('title', 200);
        $table->string('genre', 150);
        $table->unsignedSmallInteger('duration');
        $table->string('label', 50)->default('ĐANG CHIẾU');
        $table->text('poster')->nullable();
        $table->text('description')->nullable();
        $table->enum('status', ['now_showing', 'coming_soon', 'inactive'])
              ->default('now_showing');
        $table->timestamps();
    });
}

};
