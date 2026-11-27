<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_tier')->default('new')->after('role');
            $table->unsignedInteger('loyalty_points')->default(0)->after('member_tier');
        });

        DB::table('users')->whereNull('member_tier')->orWhere('member_tier', '')->update([
            'member_tier' => 'new',
            'loyalty_points' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['member_tier', 'loyalty_points']);
        });
    }
};
