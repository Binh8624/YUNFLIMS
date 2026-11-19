<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE pricing_rules
            MODIFY day_type ENUM('any', 'weekday', 'weekend', 'holiday')
            NOT NULL DEFAULT 'any'
        ");
    }

    public function down(): void
    {
        DB::table('pricing_rules')
            ->where('day_type', 'holiday')
            ->update(['day_type' => 'any']);

        DB::statement("
            ALTER TABLE pricing_rules
            MODIFY day_type ENUM('any', 'weekday', 'weekend')
            NOT NULL DEFAULT 'any'
        ");

        Schema::dropIfExists('pricing_holidays');
    }
};