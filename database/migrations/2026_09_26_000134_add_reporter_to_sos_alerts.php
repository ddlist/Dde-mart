<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart — SOS reporter (original migration). API-raised alerts carry the
 * reporter's phone so the safety inbox knows who needs help.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->string('reporter_type', 20)->nullable()->after('order_ref');
            $table->string('reporter_ref', 50)->nullable()->after('reporter_type');
        });
    }

    public function down(): void
    {
        Schema::table('sos_alerts', function (Blueprint $table) {
            $table->dropColumn(['reporter_type', 'reporter_ref']);
        });
    }
};
