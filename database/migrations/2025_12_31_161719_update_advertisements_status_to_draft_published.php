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
        // Step 1: Convert ENUM to VARCHAR temporarily so we can update values freely
        \DB::statement("ALTER TABLE advertisements MODIFY COLUMN status VARCHAR(20) DEFAULT 'draft'");
        
        // Step 2: Update existing records: active/scheduled -> published, others -> draft
        \DB::table('advertisements')
            ->whereIn('status', ['active', 'scheduled'])
            ->update(['status' => 'published']);
        
        \DB::table('advertisements')
            ->whereNotIn('status', ['published'])
            ->update(['status' => 'draft']);
        
        // Step 3: Convert back to ENUM with only draft/published
        \DB::statement("ALTER TABLE advertisements MODIFY COLUMN status ENUM('draft', 'published') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back to original values
        \DB::statement("ALTER TABLE advertisements MODIFY COLUMN status ENUM('draft', 'scheduled', 'active', 'paused', 'expired') DEFAULT 'draft'");
        
        // Revert status changes (approximate - we can't know exactly what they were)
        \DB::table('advertisements')
            ->where('status', 'published')
            ->update(['status' => 'active']);
    }
};
