<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — saved withdraw methods per requester (original).
 * Staff (or apps, later) store payout destinations once instead of
 * retyping bank details on every request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_methods', function (Blueprint $table) {
            $table->id();
            $table->string('requester_type', 30);
            $table->string('requester_ref', 100);
            $table->string('method', 30);
            $table->json('details')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['requester_type', 'requester_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_methods');
    }
};
