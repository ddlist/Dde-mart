<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart storefront — customer favorites (original migration).
 * Polymorphic hearts: products + stores for now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('favorite_type', 30); // product|store
            $table->unsignedBigInteger('favorite_id');
            $table->timestamps();

            $table->unique(['customer_id', 'favorite_type', 'favorite_id'], 'favorites_owner_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
