<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('conversation_participants', 'conversation_id')) {
                $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('conversation_participants', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('conversation_participants', 'joined_at')) {
                $table->timestamp('joined_at')->nullable();
            }
            if (!Schema::hasColumn('conversation_participants', 'last_read_at')) {
                $table->timestamp('last_read_at')->nullable();
            }
            if (!Schema::hasColumn('conversation_participants', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (! $this->indexExists('conversation_participants', 'conversation_participants_conversation_id_user_id_unique')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->unique(['conversation_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            if ($this->indexExists('conversation_participants', 'conversation_participants_conversation_id_user_id_unique')) {
                $table->dropUnique('conversation_participants_conversation_id_user_id_unique');
            }
            foreach (['conversation_id', 'user_id', 'joined_at', 'last_read_at', 'is_active'] as $column) {
                if (Schema::hasColumn('conversation_participants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
