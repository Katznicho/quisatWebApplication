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
        Schema::table('roles', function (Blueprint $table) {
            // Drop the global unique constraint on name
            $table->dropUnique(['name']);
            
            // Add composite unique constraint on business_id and name
            // This allows role names to be unique per business
            $table->unique(['business_id', 'name'], 'roles_business_id_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('roles_business_id_name_unique');
            
            // Restore the global unique constraint on name
            $table->unique(['name']);
        });
    }
};
