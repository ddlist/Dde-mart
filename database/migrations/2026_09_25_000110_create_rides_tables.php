<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — rides/cab tables (original migration).
 * Unifies car_make, car_model, vehicle_type (cab), popular_destinations, rides.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_makes', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('car_models', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('car_make_id')->nullable()->constrained('car_makes')->nullOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cab_types', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->decimal('base_fare', 10, 2)->default(0);
            $table->decimal('per_km_fare', 10, 2)->default(0);
            $table->decimal('min_fare', 10, 2)->default(0);
            $table->string('icon_path', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('title');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('image_path', 1024)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rides', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique()->nullable();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 50)->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('cab_type_id')->nullable()->constrained('cab_types')->nullOnDelete();
            $table->string('source')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tip', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method', 50)->default('cod');
            $table->timestamp('booking_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('status', 20)->default('placed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('ride_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained('rides')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_status_history');
        Schema::dropIfExists('rides');
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('cab_types');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_makes');
    }
};
