<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('tokenable');
            $table->string('device_id', 191);
            $table->text('push_token');
            $table->string('platform', 20); // ios, android, web
            $table->string('device_name')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['tokenable_type', 'tokenable_id', 'device_id']);
            $table->index(['platform', 'is_active']);
        });

        Schema::create('push_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('audience', 30); // all, parents, staff, business
            $table->unsignedBigInteger('business_id')->nullable();
            $table->json('channels'); // push, in_app
            $table->string('status', 20)->default('draft'); // draft, queued, sending, sent, failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('push_sent_count')->default(0);
            $table->unsignedInteger('push_failed_count')->default(0);
            $table->unsignedInteger('in_app_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('notifiable');
            $table->foreignId('push_broadcast_id')->nullable()->constrained('push_broadcasts')->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('push_broadcasts');
        Schema::dropIfExists('device_tokens');
    }
};
