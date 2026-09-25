<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — stores module (original migration).
 * Unifies Firestore `vendors` into MySQL. Links products.vendor_id with a real FK
 * and gives zones a legacy_id for the importer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('image_path', 1024)->nullable();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|active|suspended|rejected
            $table->boolean('is_open')->default(true);
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->decimal('min_order', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('delivery_per_km', 10, 2)->default(0);
            $table->boolean('self_delivery')->default(false);
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->string('legacy_id')->nullable()->unique()->after('id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('vendor_id', 'products_vendor_id_foreign')
                ->references('id')->on('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_vendor_id_foreign');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->dropUnique(['zones_legacy_id_unique']);
            $table->dropColumn('legacy_id');
        });

        Schema::dropIfExists('stores');
    }
};
