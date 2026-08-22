<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('countries') && Schema::hasColumn('countries', 'currency_code')) {
            DB::table('countries')
                ->where('currency_code', 'KHS')
                ->update(['currency_code' => 'KSH']);
        }

        if (Schema::hasTable('businesses') && Schema::hasColumn('businesses', 'currency_code')) {
            DB::table('businesses')
                ->where('currency_code', 'KHS')
                ->update(['currency_code' => 'KSH']);
        }

        if (Schema::hasTable('currencies')) {
            if (Schema::hasColumn('currencies', 'code')) {
                DB::table('currencies')->where('code', 'KHS')->update(['code' => 'KSH']);
            }

            if (Schema::hasColumn('currencies', 'symbol')) {
                DB::table('currencies')->where('symbol', 'KHS')->update(['symbol' => 'KSH']);
            }
        }
    }

    public function down(): void
    {
        // Display-code correction is not reversible without losing the original typo.
    }
};
