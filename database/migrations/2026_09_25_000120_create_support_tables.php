<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * DDE-Mart Admin — support & engagement tables (original migration).
 * Unifies complaints, SOS, chat_* threads, on_boarding slides.
 * content_blocks + scheduled_notifications are new (homepage/footer CMS, timed pushes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('order_ref')->nullable();
            $table->string('status', 20)->default('open'); // open|resolved|dismissed
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('order_ref')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('open'); // open|resolved
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('audience', 20)->default('admin'); // admin|driver|store|provider|worker
            $table->string('subject')->nullable();
            $table->string('last_message')->nullable();
            $table->string('status', 20)->default('open'); // open|closed
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->foreignId('thread_id')->constrained('chat_threads')->cascadeOnDelete();
            $table->string('sender_ref')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('onboarding_slides', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_id')->nullable()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path', 1024)->nullable();
            $table->string('audience', 30)->default('customer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('audience', 30)->default('customer');
            $table->string('subject');
            $table->text('message');
            $table->timestamp('send_at');
            $table->string('status', 20)->default('scheduled'); // scheduled|sent|failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('onboarding_slides');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');
        Schema::dropIfExists('sos_alerts');
        Schema::dropIfExists('complaints');
    }
};
