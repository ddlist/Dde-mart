<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — orders core (original migration).
 * Unifies Firestore `vendor_orders` into MySQL. `type` reserves parcel/rental/ride
 * modules; `vendor_id` stays plain until the Vendors module adds the FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Filled post-insert by Order::booted (race-free numbering).
            $table->string('number')->unique()->nullable();
            $table->string('type', 20)->default('food');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->json('address')->nullable();
            $table->string('payment_method', 50)->default('cod');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('tip', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('placed');
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('estimated_prep_minutes')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->json('extras')->nullable(); // [{name, price}] snapshots
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
