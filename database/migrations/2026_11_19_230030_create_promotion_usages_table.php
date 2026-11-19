
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promotion_id')
    ->constrained('promotions')
    ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Phân biệt đơn vé và đơn bắp nước
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');

            $table->decimal('discount_amount', 12, 2);

            $table->timestamp('used_at');

            $table->timestamps();

            $table->unique(
                ['order_type', 'order_id'],
                'promotion_usages_order_unique'
            );

            $table->index(['promotion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');
    }
};
