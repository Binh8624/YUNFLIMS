<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('ticket_status')->default('available')->after('qr_code');
            $table->timestamp('ticket_used_at')->nullable()->after('ticket_status');
            $table->unsignedInteger('checkin_count')->default(0)->after('ticket_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['ticket_status', 'ticket_used_at', 'checkin_count']);
        });
    }
};
