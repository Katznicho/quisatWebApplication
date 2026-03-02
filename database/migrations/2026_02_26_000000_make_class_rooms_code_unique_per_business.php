<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->unique(['business_id', 'code'], 'class_rooms_business_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropUnique('class_rooms_business_code_unique');
        });
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->unique(['code'], 'class_rooms_code_unique');
        });
    }
};
