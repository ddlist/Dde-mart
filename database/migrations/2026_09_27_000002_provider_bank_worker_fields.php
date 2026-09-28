<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — provider bank/commission + worker salary/address
 * (original migration). Mirrors the legacy provider/worker editor blocks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 150)->nullable();
            $table->string('bank_holder', 150)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('bank_other', 255)->nullable();
            $table->string('commission_type', 20)->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(0);
        });

        Schema::table('provider_workers', function (Blueprint $table) {
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('address', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('provider_workers', function (Blueprint $table) {
            $table->dropColumn(['salary', 'address']);
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name', 'bank_branch', 'bank_holder', 'bank_account',
                'bank_other', 'commission_type', 'commission_value',
            ]);
        });
    }
};
