<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_adverts', function (Blueprint $table) {
            if (! Schema::hasColumn('login_adverts', 'headline')) {
                $table->string('headline')->nullable()->after('advertiser_name');
            }
            if (! Schema::hasColumn('login_adverts', 'button_label')) {
                $table->string('button_label')->nullable()->after('destination_url');
            }
            if (! Schema::hasColumn('login_adverts', 'background_color')) {
                $table->string('background_color', 7)->nullable()->after('button_label');
            }
            if (! Schema::hasColumn('login_adverts', 'accent_color')) {
                $table->string('accent_color', 7)->nullable()->after('background_color');
            }
            if (! Schema::hasColumn('login_adverts', 'text_color')) {
                $table->string('text_color', 7)->nullable()->after('accent_color');
            }
            if (! Schema::hasColumn('login_adverts', 'duration_seconds')) {
                $table->unsignedSmallInteger('duration_seconds')->default(10)->after('text_color');
            }
            if (! Schema::hasColumn('login_adverts', 'creative_layout')) {
                $table->string('creative_layout', 20)->default('portrait')->after('creative_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_adverts', function (Blueprint $table) {
            foreach (['headline', 'button_label', 'background_color', 'accent_color', 'text_color', 'duration_seconds', 'creative_layout'] as $column) {
                if (Schema::hasColumn('login_adverts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
