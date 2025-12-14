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
            if (!Schema::hasColumn('broadcast_announcements', 'attachments')) {
                $table->json('attachments')->nullable()->after('sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_announcements', function (Blueprint $table) {
            if (Schema::hasColumn('broadcast_announcements', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });
    }
};
