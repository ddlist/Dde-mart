<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — drivers + verification (original migration).
 * Unifies role=driver app users, `documents` master, and `documents_verify` queue.
 * Verifications are polymorphic so stores (now) and owners (later) share the queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('kind', 20)->default('ride'); // ride|delivery|fleet
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('photo_path', 1024)->nullable();
            $table->string('vehicle_info')->nullable();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|active|suspended|rejected
            $table->boolean('is_online')->default(false);
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('title');
            $table->string('owner_type', 20)->default('driver'); // driver|store|owner
            $table->boolean('front_required')->default(true);
            $table->boolean('back_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('verifiable'); // driver/store (+owner later)
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->text('front_path')->nullable();
            $table->text('back_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->string('note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('drivers');
    }
};
