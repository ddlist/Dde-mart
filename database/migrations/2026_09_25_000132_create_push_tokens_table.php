<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart API — push token registry (original migration).
 * Polymorphic owner (customers now; drivers/vendors with their surfaces).
 * One row per (owner, token) — re-registering refreshes instead of duplicating.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('tokenable');
            $table->string('token', 500);
            $table->string('platform', 20)->default('android'); // android|ios|web
            $table->timestamps();

            $table->unique(['tokenable_type', 'tokenable_id', 'token'], 'push_tokens_owner_token_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
