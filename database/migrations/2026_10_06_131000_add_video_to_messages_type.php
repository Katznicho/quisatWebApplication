<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system', 'video') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("UPDATE messages SET type = 'file' WHERE type = 'video'");
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system') NOT NULL DEFAULT 'text'");
    }
};
