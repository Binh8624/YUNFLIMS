<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->string('gateway', 20);
            $table->string('method', 50)->nullable();
            $table->string('order_ref', 64)->unique();
            $table->string('transaction_code', 100)->nullable();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('VND');
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key', 64)->unique();
            $table->json('request_payload')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->unique(['gateway', 'transaction_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
