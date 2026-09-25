<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — finance + promotions tables (original migration).
 * Unifies Firestore coupons/parcel_coupons/rental_coupons/providers_coupons/promos,
 * advertisements, gift_cards, tax, currencies, subscription_plans, payouts into MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('discount_type', 20)->default('percentage'); // percentage|fixed
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->string('scope', 20)->default('all'); // all|food|parcel|rental
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->unsignedBigInteger('vendor_id')->nullable(); // FK with Vendors module
            $table->boolean('is_public')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('type', 30)->default('banner');
            $table->string('cover_path')->nullable();
            $table->string('profile_path')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('show_rating')->default(true);
            $table->boolean('show_review')->default(true);
            $table->string('status', 20)->default('pending'); // pending|approved|active|paused|rejected|expired
            $table->string('payment_status', 20)->default('pending'); // pending|paid
            $table->timestamps();
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedInteger('expiry_days')->default(365);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('country', 100)->default('Pakistan');
            $table->string('title');
            $table->string('type', 20)->default('percentage'); // percentage|fixed
            $table->decimal('value', 10, 2)->default(0);
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('symbol', 10);
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('symbol_at_right')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('free'); // free|paid
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('validity_days')->default(30);
            $table->integer('item_limit')->nullable(); // null = unlimited
            $table->integer('order_limit')->nullable();
            $table->boolean('is_commission_plan')->default(false);
            $table->json('features')->nullable();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requester_type', 20); // vendor|driver|provider|owner
            $table->unsignedBigInteger('requester_id')->nullable();
            $table->string('requester_name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('method', 30)->default('bank'); // bank|paypal|stripe|razorpay|flutterwave|cash
            $table->json('method_details')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|paid
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['requester_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('advertisements');
        Schema::dropIfExists('coupons');
    }
};
