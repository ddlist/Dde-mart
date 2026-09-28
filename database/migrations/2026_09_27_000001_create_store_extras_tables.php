<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — store gallery, working hours, special-offer slots
 * (original migration). Mirrors the legacy store editor blocks so the
 * rebuilt form reaches parity without touching existing store rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('path', 500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('store_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedTinyInteger('day')->comment('0=Sunday .. 6=Saturday');
            $table->string('opens_at', 5)->nullable();
            $table->string('closes_at', 5)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'day', 'opens_at', 'closes_at']);
        });

        Schema::create('store_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->unsignedTinyInteger('day')->comment('0=Sunday .. 6=Saturday');
            $table->string('opens_at', 5);
            $table->string('closes_at', 5);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('discount_type', 20)->default('percentage');
            $table->timestamps();
        });

        Schema::table('owners', function (Blueprint $table) {
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 150)->nullable();
            $table->string('bank_holder', 150)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('bank_other', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name', 'bank_branch', 'bank_holder',
                'bank_account', 'bank_other',
            ]);
        });

        Schema::dropIfExists('store_offers');
        Schema::dropIfExists('store_hours');
        Schema::dropIfExists('store_images');
    }
};
