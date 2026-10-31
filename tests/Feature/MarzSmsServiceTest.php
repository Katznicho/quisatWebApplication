<?php

namespace Tests\Feature;

use App\Models\MarzSmsSetting;
use App\Models\MarzSmsTopup;
use App\Services\MarzSmsService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Runs against in-memory SQLite with only the MarzSMS migration, so it never touches the app database:
 * DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=MarzSmsServiceTest
 */
class MarzSmsServiceTest extends TestCase
{
    private const BASE = 'https://sms.test/api/v1';

    protected function setUp(): void
    {
        if (getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== ':memory:') {
            $this->markTestSkipped('Run with DB_CONNECTION=sqlite DB_DATABASE=:memory:');
        }

        parent::setUp();

        // Minimal parents for the foreign keys; the full app schema is not needed here.
        foreach (['users', 'businesses'] as $table) {
            Schema::create($table, fn ($t) => $t->id());
        }

        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_31_100001_create_marzsms_tables.php']);
    }

    private function configure(): void
    {
        MarzSmsSetting::current()->update([
            'base_url' => self::BASE,
            'api_key' => 'key123',
            'api_secret' => 'secret456',
        ]);
    }

    public function test_message_is_skipped_when_marzsms_is_not_configured(): void
    {
        Http::fake();

        $sms = app(MarzSmsService::class)->sendNow('0772000111', 'Hello', 'test');

        $this->assertSame('skipped', $sms->status);
        $this->assertSame('+256772000111', $sms->recipient);
        Http::assertNothingSent();
    }

    public function test_invalid_phone_is_not_recorded(): void
    {
        $this->configure();
        Http::fake();

        $this->assertNull(app(MarzSmsService::class)->sendNow('12', 'Hello'));
        $this->assertNull(app(MarzSmsService::class)->queue(null, 'Hello'));
        Http::assertNothingSent();
    }

    public function test_successful_send_records_cost_and_provider_id(): void
    {
        $this->configure();
        Http::fake([
            self::BASE.'/sms/send' => Http::response([
                'success' => true,
                'message' => 'SMS sent to 1/1 recipient(s)',
                'data' => [
                    'total_cost' => 35,
                    'results' => [[
                        'recipient' => '+256772000111',
                        'status' => 'success',
                        'transaction_id' => 'txn-uuid-1',
                        'cost' => 35,
                    ]],
                ],
            ]),
        ]);

        $sms = app(MarzSmsService::class)->sendNow('0772 000 111', 'Your PIN is 1234', 'pocket_pay.otp');

        $this->assertSame('sent', $sms->status);
        $this->assertSame('35.00', $sms->cost);
        $this->assertSame('txn-uuid-1', $sms->provider_transaction_id);
        $this->assertNotNull($sms->sent_at);

        Http::assertSent(function (Request $request) {
            return $request->url() === self::BASE.'/sms/send'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('key123:secret456'))
                && $request['recipient'] === '+256772000111'
                && $request['message'] === 'Your PIN is 1234';
        });
    }

    public function test_insufficient_balance_marks_message_failed(): void
    {
        $this->configure();
        Http::fake([
            self::BASE.'/sms/send' => Http::response([
                'success' => false,
                'message' => 'Insufficient balance. Required: 35 UGX. Current balance: 0 UGX',
                'error' => 'insufficient_balance',
            ], 402),
        ]);

        $sms = app(MarzSmsService::class)->queue('256772000111', 'Hello', 'test');

        $this->assertSame('failed', $sms->fresh()->status);
        $this->assertStringContainsString('Insufficient balance', $sms->fresh()->error);
    }

    public function test_balance_is_parsed(): void
    {
        $this->configure();
        Http::fake([
            self::BASE.'/account/balance' => Http::response([
                'success' => true,
                'data' => ['balance' => 12500.5, 'currency' => 'UGX', 'cost_per_sms' => 35],
            ]),
        ]);

        $balance = app(MarzSmsService::class)->balance();

        $this->assertTrue($balance['success']);
        $this->assertSame(12500.5, $balance['balance']);
        $this->assertSame(35.0, $balance['cost_per_sms']);
    }

    public function test_bad_credentials_give_a_clear_message(): void
    {
        $this->configure();
        Http::fake([self::BASE.'/account/balance' => Http::response(['success' => false], 401)]);

        $balance = app(MarzSmsService::class)->balance();

        $this->assertFalse($balance['success']);
        $this->assertSame('MarzSMS rejected the API key or secret.', $balance['message']);
    }

    public function test_topup_is_initiated_and_synced(): void
    {
        $this->configure();
        Http::fake([
            self::BASE.'/account/topup' => Http::response([
                'success' => true,
                'message' => 'Top-up initiated successfully.',
                'data' => ['status' => 'pending', 'amount' => 20000, 'charge' => 600, 'total_amount' => 20600],
            ]),
            self::BASE.'/account/topup/*' => Http::response([
                'success' => true,
                'data' => ['status' => 'completed'],
            ]),
        ]);

        $service = app(MarzSmsService::class);
        $result = $service->initiateTopup(20000, '0701000222');

        $this->assertTrue($result['success']);
        $topup = MarzSmsTopup::first();
        $this->assertSame('pending', $topup->status);
        $this->assertSame('+256701000222', $topup->phone_number);
        $this->assertSame('20600.00', $topup->total_amount);

        Http::assertSent(fn (Request $request) => $request->url() === self::BASE.'/account/topup'
            && $request['reference'] === $topup->reference);

        $sync = $service->syncTopup($topup);

        $this->assertTrue($sync['changed']);
        $this->assertSame('completed', $topup->fresh()->status);
    }

    public function test_credentials_are_encrypted_at_rest(): void
    {
        $this->configure();

        $raw = DB::table('marzsms_settings')->first();

        $this->assertNotSame('key123', $raw->api_key);
        $this->assertSame('key123', MarzSmsSetting::current()->api_key);
    }
}
