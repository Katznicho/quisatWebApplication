<?php

namespace Tests\Feature;

use App\Exceptions\PocketPayException;
use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenSale;
use App\Models\ParentGuardian;
use App\Models\PocketPayCard;
use App\Models\PocketPayGatewaySetting;
use App\Models\PocketPayTopup;
use App\Models\PocketPayWithdrawal;
use App\Models\SmsMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\CanteenAttendantAuthService;
use App\Services\PocketPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\BuildsPocketPaySchema;
use Tests\TestCase;

/**
 * Runs against in-memory SQLite with a hand-built core schema plus the real Pocket Pay,
 * MarzSMS, MarzPay and Sanctum migrations, so it never touches the app database:
 * DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=PocketPayTest
 */
class PocketPayTest extends TestCase
{
    use BuildsPocketPaySchema;

    private Business $school;

    private Canteen $canteen;

    private CanteenAttendant $attendant;

    private ParentGuardian $parent;

    private Student $student;

    private PocketPayCard $card;

    private User $admin;

    protected function setUp(): void
    {
        $this->skipUnlessInMemorySqlite();

        parent::setUp();

        $this->buildSchema();
        $this->fakeProviders();

        $featureId = DB::table('features')->insertGetId(['name' => PocketPayService::FEATURE_NAME, 'created_at' => now(), 'updated_at' => now()]);

        Business::forceCreate(['name' => 'Quisat']);
        $this->school = Business::forceCreate(['name' => 'Hill Primary', 'enabled_feature_ids' => [$featureId]]);
        $this->admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@hill.test', 'password' => 'x', 'business_id' => $this->school->id]);
        $this->canteen = Canteen::create(['business_id' => $this->school->id, 'name' => 'Main Canteen']);
        $this->attendant = CanteenAttendant::create([
            'business_id' => $this->school->id,
            'canteen_id' => $this->canteen->id,
            'name' => 'Grace',
            'phone' => '+256700000001',
        ]);
        $this->parent = ParentGuardian::forceCreate([
            'first_name' => 'Ruth', 'last_name' => 'N', 'phone' => '0772000111', 'business_id' => $this->school->id,
        ]);
        $this->student = Student::forceCreate([
            'first_name' => 'Amani', 'last_name' => 'N', 'business_id' => $this->school->id, 'parent_guardian_id' => $this->parent->id,
        ]);
        $this->card = PocketPayCard::create([
            'business_id' => $this->school->id, 'student_id' => $this->student->id, 'card_number' => 'qp 0001-2345',
        ]);
    }

    // ------------------------------------------------------------------
    // Attendant onboarding & auth
    // ------------------------------------------------------------------

    public function test_attendant_sets_up_account_with_sms_otp_and_logs_in(): void
    {
        $this->postJson('/api/v1/attendant/auth/request-otp', ['phone' => '0700 000 001'])->assertOk();

        $otp = $this->lastOtp();
        $this->assertSame(CanteenAttendant::STATUS_PENDING_SETUP, $this->attendant->fresh()->status);

        $this->postJson('/api/v1/attendant/auth/login', ['phone' => '0700000001', 'pin' => '1234'])
            ->assertStatus(422)->assertJsonPath('error', 'setup_required');

        $this->postJson('/api/v1/attendant/auth/verify-otp', [
            'phone' => '0700000001', 'otp' => $otp, 'pin' => '4321', 'pin_confirmation' => '4321',
        ])->assertOk()
            ->assertJsonPath('data.attendant.canteen.name', 'Main Canteen')
            ->assertJsonPath('data.attendant.school.name', 'Hill Primary');

        $token = $this->postJson('/api/v1/attendant/auth/login', ['phone' => '+256700000001', 'pin' => '4321'])
            ->assertOk()->json('data.token');

        $this->withToken($token)->getJson('/api/v1/attendant/me')->assertOk()->assertJsonPath('data.attendant.name', 'Grace');
    }

    public function test_otp_is_single_use_and_unknown_numbers_get_the_same_answer(): void
    {
        $this->postJson('/api/v1/attendant/auth/request-otp', ['phone' => '0799999999'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, SmsMessage::count());

        $this->postJson('/api/v1/attendant/auth/request-otp', ['phone' => '0700000001']);
        $otp = $this->lastOtp();
        $payload = ['phone' => '0700000001', 'otp' => $otp, 'pin' => '4321', 'pin_confirmation' => '4321'];

        $this->postJson('/api/v1/attendant/auth/verify-otp', $payload)->assertOk();
        $this->postJson('/api/v1/attendant/auth/verify-otp', $payload)->assertStatus(422)->assertJsonPath('error', 'invalid_otp');
    }

    public function test_attendant_is_locked_after_five_wrong_pins(): void
    {
        $this->activateAttendant('4321');
        $auth = app(CanteenAttendantAuthService::class);

        for ($i = 0; $i < 5; $i++) {
            try {
                $auth->login('0700000001', '0000');
            } catch (PocketPayException) {
            }
        }

        $this->postJson('/api/v1/attendant/auth/login', ['phone' => '0700000001', 'pin' => '4321'])
            ->assertStatus(423)->assertJsonPath('error', 'account_locked');
    }

    public function test_attendant_cannot_work_when_feature_is_disabled_or_canteen_inactive(): void
    {
        $this->activateAttendant('4321');
        $this->canteen->update(['status' => 'inactive']);

        $this->postJson('/api/v1/attendant/auth/login', ['phone' => '0700000001', 'pin' => '4321'])
            ->assertStatus(403)->assertJsonPath('error', 'canteen_inactive');

        $this->canteen->update(['status' => 'active']);
        $this->school->forceFill(['enabled_feature_ids' => []])->save();

        $this->postJson('/api/v1/attendant/auth/login', ['phone' => '0700000001', 'pin' => '4321'])
            ->assertStatus(403)->assertJsonPath('error', 'feature_disabled');
    }

    public function test_attendant_tokens_are_refused_outside_the_attendant_api(): void
    {
        $token = $this->attendantToken();

        $this->withToken($token)->getJson('/api/v1/parent/pocket-pay/cards')->assertStatus(403);
        $this->withToken($token)->getJson('/api/v1/attendant/dashboard')->assertOk();
    }

    // ------------------------------------------------------------------
    // Cards & sales
    // ------------------------------------------------------------------

    public function test_attendant_activates_card_by_number_and_parent_is_told(): void
    {
        $token = $this->attendantToken();

        $this->withToken($token)->getJson('/api/v1/attendant/cards/QP00012345')
            ->assertOk()->assertJsonPath('data.card.status', 'inactive')->assertJsonPath('data.card.student.name', 'Amani N');

        $this->withToken($token)->postJson('/api/v1/attendant/cards/qp0001-2345/activate', ['pin' => '1111', 'pin_confirmation' => '1111'])
            ->assertOk()->assertJsonPath('data.card.status', 'active');

        $this->assertParentSms('pocket_pay.card_activated', 'activated by attendant Grace');
    }

    public function test_sale_debits_card_and_float_and_texts_parent(): void
    {
        $this->readyCard(balance: 10000, float: 50000);
        $token = $this->attendantToken();

        $response = $this->withToken($token)->postJson('/api/v1/attendant/sales', [
            'card_number' => 'QP00012345', 'amount' => 2500, 'pin' => '1111', 'description' => 'Lunch',
        ])->assertCreated()
            ->assertJsonPath('data.card_balance', 7500)
            ->assertJsonPath('data.float_balance', 47500);

        $this->assertStringStartsWith('QPP', $response->json('data.sale.receipt_number'));
        $this->assertSame(1, $this->card->transactions()->where('type', 'sale')->count());
        $this->assertSame(1, $this->canteen->floatTransactions()->where('type', 'sale')->count());
        $this->assertParentSms('pocket_pay.sale', 'spent UGX 2,500 at Main Canteen. Card balance: UGX 7,500');
    }

    public function test_sale_is_refused_for_wrong_pin_low_balance_daily_limit_and_low_float(): void
    {
        $this->readyCard(balance: 10000, float: 50000);
        $service = app(PocketPayService::class);

        $this->assertSaleFails(fn () => $service->makeSale($this->attendant, 'QP00012345', 1000, '9999'), 'wrong_pin');
        $this->assertSaleFails(fn () => $service->makeSale($this->attendant, 'QP00012345', 10001, '1111'), 'insufficient_card_balance');

        $this->card->forceFill(['daily_limit' => 3000])->save();
        $service->makeSale($this->attendant, 'QP00012345', 2000, '1111');
        $this->assertSaleFails(fn () => $service->makeSale($this->attendant, 'QP00012345', 1500, '1111'), 'daily_limit_exceeded');

        $this->card->forceFill(['daily_limit' => null])->save();
        $this->canteen->forceFill(['float_balance' => 500])->save();
        $this->assertSaleFails(fn () => $service->makeSale($this->attendant, 'QP00012345', 1000, '1111'), 'insufficient_float');

        $this->assertEquals(8000, $this->card->fresh()->balance);
    }

    public function test_card_locks_after_five_wrong_pins_and_a_pin_reset_unlocks_it(): void
    {
        $this->readyCard(balance: 10000, float: 50000);
        $service = app(PocketPayService::class);

        for ($i = 0; $i < 5; $i++) {
            try {
                $service->makeSale($this->attendant, 'QP00012345', 100, '0000');
            } catch (PocketPayException) {
            }
        }

        $this->assertSaleFails(fn () => $service->makeSale($this->attendant, 'QP00012345', 100, '1111'), 'card_locked');

        $service->resetCardPin($this->card->fresh(), '2222', $this->attendant);
        $service->makeSale($this->attendant, 'QP00012345', 100, '2222');
        $this->assertParentSms('pocket_pay.pin_reset', 'was reset by attendant Grace');
    }

    public function test_reversal_needs_admin_approval_before_money_moves(): void
    {
        $this->readyCard(balance: 10000, float: 50000);
        $token = $this->attendantToken();
        $sale = app(PocketPayService::class)->makeSale($this->attendant, 'QP00012345', 3000, '1111');

        $this->withToken($token)->postJson("/api/v1/attendant/sales/{$sale->uuid}/reversal", ['reason' => 'Charged twice by mistake'])
            ->assertOk()->assertJsonPath('data.sale.reversal_status', 'pending');

        $this->assertEquals(7000, $this->card->fresh()->balance);
        $this->assertEquals(47000, $this->canteen->fresh()->float_balance);

        $this->withToken($token)->postJson("/api/v1/attendant/sales/{$sale->uuid}/reversal", ['reason' => 'again please'])
            ->assertStatus(422)->assertJsonPath('error', 'reversal_not_allowed');

        app(PocketPayService::class)->approveReversal($sale->fresh(), $this->admin, 'Confirmed duplicate');

        $this->assertEquals(10000, $this->card->fresh()->balance);
        $this->assertEquals(50000, $this->canteen->fresh()->float_balance);
        $this->assertSame(CanteenSale::STATUS_REVERSED, $sale->fresh()->status);
        $this->assertParentSms('pocket_pay.reversal', 'was refunded');

        $this->expectException(PocketPayException::class);
        app(PocketPayService::class)->approveReversal($sale->fresh(), $this->admin);
    }

    public function test_rejected_reversal_keeps_the_sale(): void
    {
        $this->readyCard(balance: 10000, float: 50000);
        $sale = app(PocketPayService::class)->makeSale($this->attendant, 'QP00012345', 3000, '1111');
        app(PocketPayService::class)->requestReversal($this->attendant, $sale, 'Student changed mind');

        app(PocketPayService::class)->rejectReversal($sale->fresh(), $this->admin, 'Food was served');

        $this->assertSame(CanteenSale::STATUS_COMPLETED, $sale->fresh()->status);
        $this->assertSame(CanteenSale::REVERSAL_REJECTED, $sale->fresh()->reversal_status);
        $this->assertEquals(7000, $this->card->fresh()->balance);
    }

    public function test_attendant_cannot_see_or_reverse_another_canteens_sale(): void
    {
        $this->readyCard(balance: 10000, float: 50000);
        $sale = app(PocketPayService::class)->makeSale($this->attendant, 'QP00012345', 1000, '1111');

        $other = Canteen::create(['business_id' => $this->school->id, 'name' => 'Tuck Shop']);
        $otherAttendant = CanteenAttendant::create([
            'business_id' => $this->school->id, 'canteen_id' => $other->id, 'name' => 'Paul', 'phone' => '+256700000002',
        ]);
        $otherAttendant->forceFill(['status' => 'active'])->save();
        $this->withToken($otherAttendant->createToken('t')->plainTextToken);

        $this->getJson("/api/v1/attendant/sales/{$sale->uuid}")->assertNotFound();
        $this->postJson("/api/v1/attendant/sales/{$sale->uuid}/reversal", ['reason' => 'not my sale'])->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Parent app & top-ups
    // ------------------------------------------------------------------

    public function test_parent_activates_sets_limit_and_reads_statement(): void
    {
        $this->withToken($this->parent->createToken('t')->plainTextToken);

        $this->getJson('/api/v1/parent/pocket-pay/cards')->assertOk()->assertJsonCount(1, 'data.cards');

        $this->postJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}/activate", ['pin' => '5555', 'pin_confirmation' => '5555'])
            ->assertOk()->assertJsonPath('data.card.status', 'active');

        $this->putJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}/limit", ['daily_limit' => 4000])
            ->assertOk()->assertJsonPath('data.card.daily_limit', 4000)->assertJsonPath('data.card.remaining_today', 4000);

        $this->getJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}/statement")->assertOk()->assertJsonCount(0, 'data.transactions');
        $this->assertParentSms('pocket_pay.limit_changed', 'daily spending limit is now UGX 4,000');
    }

    public function test_parent_cannot_touch_another_familys_card(): void
    {
        $stranger = ParentGuardian::forceCreate(['first_name' => 'X', 'last_name' => 'Y', 'phone' => '0772999999', 'business_id' => $this->school->id]);
        $this->withToken($stranger->createToken('t')->plainTextToken);

        $this->getJson('/api/v1/parent/pocket-pay/cards')->assertOk()->assertJsonCount(0, 'data.cards');
        $this->getJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}")->assertNotFound();
        $this->putJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}/limit", ['daily_limit' => 1])->assertNotFound();
    }

    public function test_parent_topup_is_credited_once_from_the_marzpay_webhook(): void
    {
        $this->withToken($this->parent->createToken('t')->plainTextToken);

        $topupUuid = $this->postJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}/topups", ['amount' => 20000])
            ->assertOk()->assertJsonPath('data.topup.status', 'processing')->json('data.topup.uuid');

        $topup = PocketPayTopup::where('uuid', $topupUuid)->firstOrFail();
        $reference = $topup->latestPaymentCollection()->reference;
        $this->assertSame('+256772000111', $topup->phone_number);

        $webhook = ['event_type' => 'collection.completed', 'transaction' => ['reference' => $reference, 'status' => 'completed']];
        $this->postJson('/api/v1/webhooks/marzpay', $webhook)->assertOk();
        $this->postJson('/api/v1/webhooks/marzpay', $webhook)->assertOk();

        $this->assertEquals(20000, $this->card->fresh()->balance);
        $this->assertSame(1, $this->card->transactions()->where('type', 'topup')->count());
        $this->assertSame('completed', $topup->fresh()->status);
        $this->assertNull(DB::table('businesses')->where('id', $this->school->id)->value('available_balance'));
        $this->assertParentSms('pocket_pay.topup', 'UGX 20,000 was added');
    }

    public function test_pocket_pay_webhook_is_rejected_unless_the_marz_signature_matches(): void
    {
        PocketPayGatewaySetting::current()->update(['marzpay_webhook_secret' => 'whsec_pocket']);

        $this->withToken($this->parent->createToken('t')->plainTextToken);
        $topupUuid = $this->postJson("/api/v1/parent/pocket-pay/cards/{$this->card->uuid}/topups", ['amount' => 20000])
            ->assertOk()->json('data.topup.uuid');

        $topup = PocketPayTopup::where('uuid', $topupUuid)->firstOrFail();
        $body = json_encode([
            'event_type' => 'collection.completed',
            'transaction' => ['reference' => $topup->latestPaymentCollection()->reference, 'status' => 'completed'],
        ]);

        $this->call('POST', '/api/v1/webhooks/marzpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_MARZPAY_TIMESTAMP' => '1712345678',
            'HTTP_X_MARZPAY_SIGNATURE' => 't=1712345678,v1=deadbeef',
        ], $body)->assertStatus(401);

        $this->assertEquals(0, $this->card->fresh()->balance);

        $timestamp = '1712345678';
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_pocket');
        $this->call('POST', '/api/v1/webhooks/marzpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_MARZPAY_TIMESTAMP' => $timestamp,
            'HTTP_X_MARZPAY_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $body)->assertOk();

        $this->assertEquals(20000, $this->card->fresh()->balance);
        $this->assertSame('completed', $topup->fresh()->status);
    }

    public function test_pocket_pay_withdrawal_webhook_updates_status_when_signed(): void
    {
        PocketPayGatewaySetting::current()->update(['marzpay_webhook_secret' => 'whsec_pocket']);

        $withdrawal = PocketPayWithdrawal::query()->create([
            'amount' => 5000,
            'method' => PocketPayWithdrawal::METHOD_MOBILE,
            'phone_number' => '+256772000111',
            'status' => 'processing',
        ]);

        $body = json_encode([
            'event_type' => 'disbursement.completed',
            'transaction' => [
                'reference' => $withdrawal->uuid,
                'status' => 'completed',
                'uuid' => 'marz-uuid-1',
                'provider_reference' => 'mm-99',
            ],
        ]);
        $timestamp = '1712345678';
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_pocket');

        $this->call('POST', '/api/v1/webhooks/marzpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertStatus(401);

        $this->assertSame('processing', $withdrawal->fresh()->status);

        $this->call('POST', '/api/v1/webhooks/marzpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_MARZPAY_TIMESTAMP' => $timestamp,
            'HTTP_X_MARZPAY_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $body)->assertOk();

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('marz-uuid-1', $withdrawal->marz_transaction_uuid);
        $this->assertSame('mm-99', $withdrawal->provider_reference);
    }

    public function test_canteen_float_topup_credits_the_float(): void
    {
        $result = app(PocketPayService::class)->initiateTopup($this->canteen, 100000, '0701000222', $this->admin);

        app(PocketPayService::class)->creditTopup($result['topup']);
        app(PocketPayService::class)->creditTopup($result['topup']);

        $this->assertEquals(100000, $this->canteen->fresh()->float_balance);
        $this->assertSame(1, $this->canteen->floatTransactions()->where('type', 'topup')->count());
    }

    public function test_topup_amount_bounds(): void
    {
        $this->expectException(PocketPayException::class);
        app(PocketPayService::class)->initiateTopup($this->card, 100, '0772000111', $this->parent);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    public function test_collect_canteen_sale_adds_to_the_balance_and_only_the_assigned_person_can_withdraw(): void
    {
        $this->readyCard(balance: 10000, float: 0);
        $this->canteen->forceFill([
            'settlement_mode' => Canteen::MODE_COLLECT,
            'float_balance' => 0,
        ])->save();
        $this->attendant->forceFill(['can_withdraw' => true])->save();

        $service = app(PocketPayService::class);
        $service->makeSale($this->attendant, 'QP00012345', 2500, '1111');

        $this->assertEquals(7500, $this->card->fresh()->balance);
        $this->assertEquals(2500, $this->canteen->fresh()->float_balance);

        $sale = CanteenSale::query()->firstOrFail();
        $service->requestReversal($this->attendant, $sale, 'Wrong item');
        $service->approveReversal($sale->fresh(), $this->admin, 'ok');
        $this->assertEquals(10000, $this->card->fresh()->balance);
        $this->assertEquals(0, $this->canteen->fresh()->float_balance);

        $service->makeSale($this->attendant, 'QP00012345', 2500, '1111');
        $other = CanteenAttendant::create([
            'business_id' => $this->school->id,
            'canteen_id' => $this->canteen->id,
            'name' => 'Peter',
            'phone' => '+256700000002',
            'status' => CanteenAttendant::STATUS_ACTIVE,
        ]);

        try {
            $service->withdrawCanteenBalance($other, $this->canteen->fresh(), 2500, '0772000111');
            $this->fail('Expected the withdrawal to be refused.');
        } catch (PocketPayException $e) {
            $this->assertSame('withdraw_forbidden', $e->errorCode);
        }

        Http::fake([
            'https://pay.test/api/v1/send-money' => Http::response([
                'status' => 'success',
                'data' => ['transaction' => ['uuid' => 'wd-canteen', 'status' => 'completed', 'provider_reference' => 'MM9']],
            ]),
        ]);

        $token = $this->attendantToken();
        $this->withToken($token)->postJson('/api/v1/attendant/withdrawals', [
            'amount' => 2500,
            'phone_number' => '0772000111',
            'notes' => 'Week',
        ])->assertCreated()
            ->assertJsonPath('data.canteen.float_balance', 0)
            ->assertJsonPath('data.canteen.settlement_mode', 'collect')
            ->assertJsonPath('data.withdrawal.status', 'completed');

        $this->assertEquals(0, $this->canteen->fresh()->float_balance);
        $this->assertSame($this->attendant->id, PocketPayWithdrawal::query()->value('canteen_attendant_id'));

        try {
            $service->recordFloatTopup($this->canteen->fresh(), 5000, 'proof.jpg', $this->admin);
            $this->fail('Expected a float top-up to be refused.');
        } catch (PocketPayException $e) {
            $this->assertSame('float_not_used', $e->errorCode);
        }
    }

    private function readyCard(float $balance, float $float): void
    {
        app(PocketPayService::class)->activateCard($this->card, '1111', $this->attendant);
        $this->card->forceFill(['balance' => $balance])->save();
        $this->canteen->forceFill(['float_balance' => $float])->save();
        $this->activateAttendant('4321');
    }

    private function activateAttendant(string $pin): void
    {
        $this->attendant->forceFill(['pin' => bcrypt($pin), 'status' => CanteenAttendant::STATUS_ACTIVE])->save();
    }

    private function attendantToken(): string
    {
        $this->activateAttendant('4321');

        return $this->postJson('/api/v1/attendant/auth/login', ['phone' => '0700000001', 'pin' => '4321'])->assertOk()->json('data.token');
    }

    private function lastOtp(): string
    {
        $sms = SmsMessage::where('purpose', 'pocket_pay.attendant_otp')->latest('id')->firstOrFail();
        $this->assertSame('sent', $sms->status);
        $this->assertLessThanOrEqual(160, strlen($sms->message));
        preg_match('/one-time PIN is (\d{4})/', $sms->message, $m);

        return $m[1];
    }

    private function assertParentSms(string $purpose, string $contains): void
    {
        $sms = SmsMessage::where('purpose', $purpose)->latest('id')->first();

        $this->assertNotNull($sms, "No {$purpose} SMS recorded");
        $this->assertSame('+256772000111', $sms->recipient);
        $this->assertStringContainsString($contains, $sms->message);
        $this->assertLessThanOrEqual(160, strlen($sms->message));
        $this->assertSame('sent', $sms->status);
    }

    private function assertSaleFails(callable $sale, string $errorCode): void
    {
        try {
            $sale();
            $this->fail("Expected sale to fail with {$errorCode}");
        } catch (PocketPayException $e) {
            $this->assertSame($errorCode, $e->errorCode, $e->getMessage());
        }
    }
}
