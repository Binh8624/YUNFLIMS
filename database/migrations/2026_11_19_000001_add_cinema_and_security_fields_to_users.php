<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cinema_id')->nullable()->after('is_active')->constrained('cinemas')->nullOnDelete();
            $table->timestamp('locked_until')->nullable()->after('cinema_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cinema_id');
            $table->dropColumn('locked_until');
        });
    }
};
