<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — driver bank details + fleet linkage (original).
 * Fleet drivers belong to an owner; store-linked drivers (store_id) act as
 * the lightweight deliverymen list. Mirrors the legacy blocks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 150)->nullable();
            $table->string('bank_holder', 150)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('bank_other', 255)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn([
                'bank_name', 'bank_branch', 'bank_holder',
                'bank_account', 'bank_other',
            ]);
        });
    }
};
