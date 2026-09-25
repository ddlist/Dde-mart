<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — parcel + rental tables (original migration).
 * Unifies parcel_categories, parcel_weight, parcel_orders, rental_packages,
 * rental_vehicle_type, rental_orders collections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_categories', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image_path', 1024)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('parcel_weights', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('title');
            $table->decimal('max_kg', 8, 2)->nullable();
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('parcel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->nullable();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('sender_name')->nullable();
            $table->string('sender_phone', 50)->nullable();
            $table->json('sender_address')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone', 50)->nullable();
            $table->json('receiver_address')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('parcel_categories')->nullOnDelete();
            $table->foreignId('weight_id')->nullable()->constrained('parcel_weights')->nullOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method', 50)->default('cod');
            $table->boolean('collect_by_receiver')->default(false);
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('status', 20)->default('placed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('parcel_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_order_id')->constrained('parcel_orders')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('rental_vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->string('icon_path', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rental_packages', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('rental_vehicle_types')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('included_hours', 8, 2)->default(0);
            $table->decimal('included_km', 8, 2)->default(0);
            $table->decimal('extra_km_fare', 10, 2)->default(0);
            $table->decimal('extra_minute_fare', 10, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rental_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->nullable();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 50)->nullable();
            $table->foreignId('package_id')->nullable()->constrained('rental_packages')->nullOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('rental_vehicle_types')->nullOnDelete();
            $table->string('source')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tip', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method', 50)->default('cod');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('booking_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 20)->default('placed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('rental_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained('rental_orders')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_status_history');
        Schema::dropIfExists('rental_orders');
        Schema::dropIfExists('rental_packages');
        Schema::dropIfExists('rental_vehicle_types');
        Schema::dropIfExists('parcel_status_history');
        Schema::dropIfExists('parcel_orders');
        Schema::dropIfExists('parcel_weights');
        Schema::dropIfExists('parcel_categories');
    }
};
