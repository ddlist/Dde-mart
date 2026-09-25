<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — catalog slice 3: banners (original migration).
 * Unifies Firestore `banner_items`. Paid `advertisements` belong to D8 Promotions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('title');
            $table->string('image_path')->nullable();
            $table->string('redirect_type', 20)->default('none'); // none|product|category|store|url
            $table->string('redirect_target')->nullable(); // id or URL depending on type
            $table->string('position', 20)->default('home');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
