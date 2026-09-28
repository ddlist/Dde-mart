<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — variant pricing/qty on the product/attribute pivot plus
 * a free-form specs JSON on products (original migration). Mirrors the
 * legacy variant rows (price/quantity/image-less) and specification lists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute_value', function (Blueprint $table) {
            $table->decimal('price_delta', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('specs')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specs');
        });

        Schema::table('product_attribute_value', function (Blueprint $table) {
            $table->dropColumn(['price_delta', 'quantity']);
        });
    }
};
