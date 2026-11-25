
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gift_card_id')
    ->constrained('gift_cards')
    ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // issued, redeemed, refunded, adjusted
            $table->string('type');

            $table->decimal('amount', 12, 2);

            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['gift_card_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
    }
};
