<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — products legacy ref (original migration).
 * Reviews reference the Firestore doc `id` FIELD while imports key on the doc key;
 * storing both keeps review linking working on fresh and repeat runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('legacy_ref')->nullable()->after('legacy_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('legacy_ref');
        });
    }
};
