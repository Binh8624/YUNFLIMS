<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('status');
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->string('payment_reference')->nullable()->unique()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
            $table->text('qr_code')->nullable()->after('paid_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed'");
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'payment_reference', 'paid_at', 'qr_code']);
        });
    }
};
