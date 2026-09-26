<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart — chat participant links (original migration). Threads created
 * from an order/booking number carry the linked store/driver/provider so
 * the workforce apps get their own inbox slices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->string('order_ref', 50)->nullable()->after('subject');
            $table->unsignedBigInteger('vendor_id')->nullable()->after('order_ref');
            $table->unsignedBigInteger('driver_id')->nullable()->after('vendor_id');
            $table->unsignedBigInteger('provider_id')->nullable()->after('driver_id');
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn(['order_ref', 'vendor_id', 'driver_id', 'provider_id']);
        });
    }
};
