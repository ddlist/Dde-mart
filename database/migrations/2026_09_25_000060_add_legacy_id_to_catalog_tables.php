<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — legacy import keys (original migration).
 * `legacy_id` holds the Firestore document id so the D10 importer is idempotent
 * and later owner-module imports can resolve cross-collection references.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sections', 'categories', 'brands', 'products', 'coupons'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('legacy_id')->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (['sections', 'categories', 'brands', 'products', 'coupons'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropUnique([$table->getTable().'_legacy_id_unique']);
                $table->dropColumn('legacy_id');
            });
        }
    }
};
