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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // Allows for soft deletion of roles
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
