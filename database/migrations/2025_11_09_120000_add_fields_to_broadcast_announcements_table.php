<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('broadcast_announcements', 'business_id')) {
                $table->foreignId('business_id')->after('id')->constrained()->cascadeOnDelete();
            }

            if (!Schema::hasColumn('broadcast_announcements', 'sender_id')) {
                $table->foreignId('sender_id')->after('business_id')->constrained('users')->cascadeOnDelete();
            }

            if (!Schema::hasColumn('broadcast_announcements', 'title')) {
                $table->string('title')->after('sender_id');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'content')) {
                $table->text('content')->after('title');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'type')) {
                $table->string('type')->default('general')->after('content');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'channels')) {
                $table->json('channels')->nullable()->after('type');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'target_roles')) {
                $table->json('target_roles')->nullable()->after('channels');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'target_users')) {
                $table->json('target_users')->nullable()->after('target_roles');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'status')) {
                $table->string('status')->default('draft')->after('target_users');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('broadcast_announcements', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            if (Schema::hasColumn('broadcast_announcements', 'sender_id')) {
                $table->dropForeign(['sender_id']);
            }
            if (Schema::hasColumn('broadcast_announcements', 'business_id')) {
                $table->dropForeign(['business_id']);
            }

            $dropColumns = [
                'sent_at',
                'scheduled_at',
                'status',
                'target_users',
                'target_roles',
                'channels',
                'type',
                'content',
                'title',
                'sender_id',
                'business_id',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('broadcast_announcements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
