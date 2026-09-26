<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart — auto-dispatch fields (original migration). Food orders carry the
 * offered driver + offer deadline + rejected list; drivers carry coordinates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->nullable()->after('vendor_id');
            $table->timestamp('dispatch_expires_at')->nullable()->after('driver_id');
            $table->json('rejected_driver_ids')->nullable()->after('dispatch_expires_at');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('store_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('location_updated_at')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['driver_id', 'dispatch_expires_at', 'rejected_driver_ids']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_updated_at']);
        });
    }
};
