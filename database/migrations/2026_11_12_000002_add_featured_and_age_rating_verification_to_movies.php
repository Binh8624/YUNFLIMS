<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->boolean('age_rating_verified')->default(false)->after('age_rating');
            $table->boolean('is_featured')->default(false)->after('age_rating_verified')->index();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropIndex(['is_featured']);
            $table->dropColumn(['age_rating_verified', 'is_featured']);
        });
    }
};
