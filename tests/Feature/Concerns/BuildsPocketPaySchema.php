<?php

namespace Tests\Feature\Concerns;

use App\Models\MarzSmsSetting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal in-memory SQLite schema for Pocket Pay tests: hand-built core tables plus the real
 * Pocket Pay, MarzSMS, MarzPay and Sanctum migrations. Never touches the app database.
 */
trait BuildsPocketPaySchema
{
    protected function skipUnlessInMemorySqlite(): void
    {
        if (getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== ':memory:') {
            $this->markTestSkipped('Run with DB_CONNECTION=sqlite DB_DATABASE=:memory:');
        }
    }

    protected function fakeProviders(): void
    {
        MarzSmsSetting::current()->update([
            'base_url' => 'https://sms.test/api/v1', 'api_key' => 'k', 'api_secret' => 's',
        ]);

        Http::fake([
            'https://sms.test/api/v1'.'/sms/send' => Http::response([
                'success' => true,
                'data' => ['results' => [['status' => 'success', 'transaction_id' => 'sms-1', 'cost' => 35]]],
            ]),
            '*/collect-money' => Http::response([
                'status' => 'success',
                'message' => 'Collection initiated',
                'data' => ['transaction' => ['uuid' => 'marz-txn-1', 'status' => 'processing']],
            ]),
        ]);
    }

    protected function buildSchema(): void
    {
        Schema::create('users', function ($t) {
            $t->id();
            $t->uuid('uuid')->nullable();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('role_id')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
        });
        Schema::create('roles', function ($t) {
            $t->id();
            $t->uuid('uuid')->nullable();
            $t->string('name');
            $t->text('permissions')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('businesses', function ($t) {
            $t->id();
            $t->uuid('uuid')->nullable();
            $t->string('name');
            $t->string('logo')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->string('timezone')->nullable();
            $t->text('enabled_feature_ids')->nullable();
            $t->unsignedBigInteger('business_category_id')->nullable();
            $t->decimal('available_balance', 15, 2)->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('features', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('class_rooms', function ($t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('parent_guardians', function ($t) {
            $t->id();
            $t->uuid('uuid')->nullable();
            $t->string('first_name');
            $t->string('last_name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->string('account_type')->nullable();
            $t->string('universal_code')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('students', function ($t) {
            $t->id();
            $t->uuid('uuid')->nullable();
            $t->string('first_name');
            $t->string('last_name');
            $t->string('student_id')->nullable();
            $t->string('photo')->nullable();
            $t->unsignedBigInteger('business_id')->nullable();
            $t->unsignedBigInteger('class_room_id')->nullable();
            $t->unsignedBigInteger('parent_guardian_id')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        foreach ([
            '2019_12_14_000001_create_personal_access_tokens_table.php',
            '2026_06_18_000001_create_payment_collections_table.php',
            '2026_06_18_100001_create_marzpay_settings_table.php',
            '2026_06_18_100002_add_charge_fields_to_payment_collections_table.php',
            '2026_06_19_100005_add_business_credited_at_to_payment_collections_table.php',
            '2026_10_31_100001_create_marzsms_tables.php',
            '2026_10_31_120001_create_pocket_pay_tables.php',
            '2026_11_01_100001_create_pocket_pay_card_numbers_table.php',
            '2026_11_01_120001_create_canteen_float_topups_table.php',
        ] as $migration) {
            Artisan::call('migrate', ['--path' => 'database/migrations/'.$migration]);
        }
    }
}
