<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — widen image path columns (original migration).
 * Legacy Firebase Storage URLs (with tokens) exceed 255 chars. TEXT it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sections', 'categories', 'brands', 'products', 'banners', 'coupons', 'gift_cards', 'subscription_plans'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('image_path')->nullable()->change();
            });
        }

        Schema::table('advertisements', function (Blueprint $table) {
            $table->text('cover_path')->nullable()->change();
            $table->text('profile_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        foreach (['sections', 'categories', 'brands', 'products', 'banners', 'coupons', 'gift_cards', 'subscription_plans'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('image_path')->nullable()->change();
            });
        }

        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->change();
            $table->string('profile_path')->nullable()->change();
        });
    }
};
