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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->string('name');
            $table->string('code');
            $table->string('symbol');
            $table->string('rate');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('position', ['left', 'right' ,'left_with_space' ,'right_with_space'])->default('left');
            $table->boolean('is_default')->default(false);  
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
