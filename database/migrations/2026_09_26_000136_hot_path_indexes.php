<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart — hot-path composite indexes (original migration). Port of the
 * legacy Firestore index map: every composite here backs one frequent
 * WHERE + ORDER combination (inboxes, timelines, ledgers). Deliberately
 * consolidated — MySQL leftmost prefixes cover what needed 259 entries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['vendor_id', 'status', 'created_at'], 'orders_vendor_status_time');
            $table->index(['driver_id', 'status'], 'orders_driver_status');
            $table->index(['status', 'created_at'], 'orders_status_time');
        });

        Schema::table('parcel_orders', function (Blueprint $table) {
            $table->index(['driver_id', 'status'], 'parcel_driver_status');
            $table->index(['sender_phone', 'status'], 'parcel_sender_status');
        });

        Schema::table('rental_orders', function (Blueprint $table) {
            $table->index(['driver_id', 'status'], 'rental_driver_status');
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->index(['driver_id', 'status'], 'rides_driver_status');
            $table->index(['customer_phone', 'status'], 'rides_customer_status');
        });

        Schema::table('provider_bookings', function (Blueprint $table) {
            $table->index(['provider_id', 'status'], 'bookings_provider_status');
        });

        Schema::table('table_bookings', function (Blueprint $table) {
            $table->index(['store_id', 'status'], 'dinein_store_status');
        });

        Schema::table('wallet_entries', function (Blueprint $table) {
            $table->index(['owner_type', 'owner_ref'], 'wallet_owner');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['section_id', 'category_id'], 'products_section_category');
            $table->index(['vendor_id', 'is_active'], 'products_vendor_active');
        });

        Schema::table('push_tokens', function (Blueprint $table) {
            $table->unique(['tokenable_type', 'tokenable_id', 'token'], 'push_tokens_owner_token');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index(['thread_id', 'id'], 'chat_thread_order');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['audience', 'created_at'], 'notifications_audience_time');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_vendor_status_time');
            $table->dropIndex('orders_driver_status');
            $table->dropIndex('orders_status_time');
        });

        Schema::table('parcel_orders', function (Blueprint $table) {
            $table->dropIndex('parcel_driver_status');
            $table->dropIndex('parcel_sender_status');
        });

        Schema::table('rental_orders', function (Blueprint $table) {
            $table->dropIndex('rental_driver_status');
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->dropIndex('rides_driver_status');
            $table->dropIndex('rides_customer_status');
        });

        Schema::table('provider_bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_provider_status');
        });

        Schema::table('table_bookings', function (Blueprint $table) {
            $table->dropIndex('dinein_store_status');
        });

        Schema::table('wallet_entries', function (Blueprint $table) {
            $table->dropIndex('wallet_owner');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_section_category');
            $table->dropIndex('products_vendor_active');
        });

        Schema::table('push_tokens', function (Blueprint $table) {
            $table->dropUnique('push_tokens_owner_token');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('chat_thread_order');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_audience_time');
        });
    }
};
