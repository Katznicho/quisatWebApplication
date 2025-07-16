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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            //branch_id is nullable to allow rooms to be created without a specific branch
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes(); // Allows for soft deletion of rooms
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
