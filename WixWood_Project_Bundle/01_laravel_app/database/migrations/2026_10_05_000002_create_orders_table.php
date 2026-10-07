<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            // Human-friendly id (e.g. WW-ABC123), not an autoincrement int,
            // so it's safe to show to the customer as their order reference.
            $table->string('id', 32)->primary();

            $table->string('customer_name', 120);
            $table->string('phone', 40);
            $table->string('email', 160)->nullable();
            $table->string('address', 255);
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->text('notes')->nullable();

            // Line items as captured at order time (id, name, qty, unitPrice,
            // lineTotal) — a historical snapshot, not a live join to the
            // catalog, so a later price change never rewrites a past order.
            $table->json('items');

            $table->string('currency', 8)->default('NGN');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('delivery_fee');
            $table->unsignedBigInteger('total');
            $table->string('delivery_zone', 40)->nullable();

            $table->string('payment_provider', 20)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->enum('payment_status', ['unpaid', 'paid', 'failed'])->default('unpaid');

            $table->enum('order_status', [
                'pending_payment', 'paid', 'processing', 'in_production',
                'ready_for_delivery', 'delivered', 'cancelled',
            ])->default('pending_payment');

            $table->timestamps();

            $table->index('order_status');
            $table->index('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
