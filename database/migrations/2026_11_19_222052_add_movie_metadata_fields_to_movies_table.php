<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('slug', 220)->nullable()->unique();
            $table->date('release_date')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('director', 150)->nullable();
            $table->text('cast_members')->nullable();
            $table->string('original_language', 100)->nullable();
            $table->string('subtitle_languages', 255)->nullable();
            $table->boolean('is_dubbed')->default(false);
            $table->json('gallery')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'release_date',
                'country',
                'director',
                'cast_members',
                'original_language',
                'subtitle_languages',
                'is_dubbed',
                'gallery',
            ]);
        });
    }
};