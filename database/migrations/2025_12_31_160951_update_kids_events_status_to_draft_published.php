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
        Schema::table('kids_events', function (Blueprint $table) {
            // Change default status to 'draft'
            $table->string('status')->default('draft')->change();
        });

        // Update existing records: change 'upcoming' to 'draft', others to 'published'
        \DB::table('kids_events')
            ->whereIn('status', ['upcoming', 'ongoing'])
            ->update(['status' => 'draft']);
        
        \DB::table('kids_events')
            ->whereIn('status', ['completed', 'cancelled'])
            ->update(['status' => 'published']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kids_events', function (Blueprint $table) {
            // Revert to original default
            $table->string('status')->default('upcoming')->change();
        });

        // Revert status changes (approximate - we can't know exactly what they were)
        \DB::table('kids_events')
            ->where('status', 'draft')
            ->update(['status' => 'upcoming']);
    }
};
