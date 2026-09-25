<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — permissions table (original migration).
 * Replaces legacy `permissions` (role_id, permission, routes) triple rows.
 * One row per granted ability; key format: "<group>.<ability>" (e.g. "roles.edit").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('group');
            $table->string('ability');
            $table->timestamps();

            $table->unique(['role_id', 'group', 'ability']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
