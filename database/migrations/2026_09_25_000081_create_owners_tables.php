<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — owners, wallet ledger, referrals (original migration).
 * Owners are role=vendor app accounts; stores.owner_id links them.
 * Wallet entries are a read-only ledger (owner refs stay legacy strings until
 * customer auth lands); adjustments arrive with the finance follow-up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('status', 20)->default('pending'); // pending|active|suspended|rejected
            $table->timestamps();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('zone_id')
                ->constrained('owners')->nullOnDelete();
        });

        Schema::create('wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('owner_type', 20)->default('customer'); // customer|driver|vendor|provider
            $table->string('owner_ref')->nullable(); // legacy user id (resolved later)
            $table->decimal('amount', 10, 2)->default(0); // signed
            $table->string('kind', 30)->default('order'); // topup|order|payout|subscription|adjustment
            $table->string('method', 50)->nullable();
            $table->string('status', 20)->default('success');
            $table->string('note')->nullable();
            $table->string('order_ref')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'status']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('referrer_ref')->nullable(); // legacy user id
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('wallet_entries');

        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });

        Schema::dropIfExists('owners');
    }
};
