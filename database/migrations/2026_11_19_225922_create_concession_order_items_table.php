<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concession_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('concession_order_id')
                ->constrained('concession_orders')
                ->cascadeOnDelete();

            $table->foreignId('concession_product_id')
                ->nullable()
                ->constrained('concession_products')
                ->nullOnDelete();

            $table->string('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();

            $table->index('concession_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concession_order_items');
    }
};