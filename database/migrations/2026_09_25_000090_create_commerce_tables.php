<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — reviews, subscriptions, gifts ledger, disbursements,
 * stories, filter presets (original migration). Unifies items_review,
 * review_attributes, subscription_history, gift_purchases, story collections
 * and the per-vendor filter maps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('title');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('item_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('comment')->nullable();
            $table->json('criteria_scores')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('subscriber_type', 20)->default('owner'); // owner|driver|unknown
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->string('subscriber_ref')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 20)->default('active'); // active|expired|cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gift_orders', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('gift_id')->nullable()->constrained('gift_cards')->nullOnDelete();
            $table->string('code')->nullable();
            $table->string('buyer_ref')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 20)->default('active'); // active|redeemed|expired
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->string('method', 30)->default('bank');
            $table->string('status', 20)->default('pending'); // pending|paid
            $table->text('note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disbursement_payout', function (Blueprint $table) {
            $table->foreignId('disbursement_id')->constrained('disbursements')->cascadeOnDelete();
            $table->foreignId('payout_request_id')->constrained('payout_requests')->cascadeOnDelete();
            $table->primary(['disbursement_id', 'payout_request_id']);
        });

        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->text('video_url')->nullable();
            $table->text('thumbnail')->nullable();
            $table->string('status', 20)->default('active'); // active|removed
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('filter_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filter_presets');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('disbursement_payout');
        Schema::dropIfExists('disbursements');
        Schema::dropIfExists('gift_orders');
        Schema::dropIfExists('plan_subscriptions');
        Schema::dropIfExists('item_reviews');
        Schema::dropIfExists('review_criteria');
    }
};
