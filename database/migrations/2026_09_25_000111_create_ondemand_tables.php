<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — on-demand + dine-in tables (original migration).
 * Unifies provider_categories, providers_services, providers_workers,
 * provider_orders, booked_table. Providers themselves are role=provider accounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('status', 20)->default('pending'); // pending|active|suspended|rejected
            $table->timestamps();
        });

        Schema::create('provider_categories', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('parent_id')->nullable()->constrained('provider_categories')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->unsignedInteger('level')->default(0);
            $table->string('title');
            $table->string('image_path', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('provider_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('price_unit', 30)->nullable();
            $table->string('image_path', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('provider_workers', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('provider_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->nullable();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 50)->nullable();
            $table->foreignId('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('provider_services')->nullOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('provider_workers')->nullOnDelete();
            $table->string('address')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('extra_charges', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method', 50)->default('cod');
            $table->string('status', 20)->default('placed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_booking_id')->constrained('provider_bookings')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('table_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('guest_name');
            $table->string('guest_phone', 50)->nullable();
            $table->string('guest_email')->nullable();
            $table->unsignedInteger('guests')->default(2);
            $table->timestamp('booked_for')->nullable();
            $table->string('occasion')->nullable();
            $table->text('special_request')->nullable();
            $table->string('status', 20)->default('pending'); // pending|confirmed|seated|completed|cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_bookings');
        Schema::dropIfExists('booking_status_history');
        Schema::dropIfExists('provider_bookings');
        Schema::dropIfExists('provider_workers');
        Schema::dropIfExists('provider_services');
        Schema::dropIfExists('provider_categories');
        Schema::dropIfExists('providers');
    }
};
