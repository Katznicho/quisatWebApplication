<?php

namespace Tests\Feature;

use App\Livewire\PocketPay\CanteenDetail;
use App\Livewire\PocketPay\ListCanteens;
use App\Livewire\PocketPay\ListCanteenSales;
use App\Livewire\PocketPay\ListPocketPayCardNumbers;
use App\Livewire\PocketPay\ListPocketPayCards;
use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
use App\Models\CanteenFloatTopup;
use App\Models\CanteenSale;
use App\Models\ParentGuardian;
use App\Models\PocketPayCard;
use App\Models\PocketPayCardNumber;
use App\Models\PocketPayTopup;
use App\Models\Role;
use App\Models\SmsMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\PocketPayService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsPocketPaySchema;
use Tests\TestCase;

/**
 * Web dashboard (Livewire/Filament) for Pocket Pay.
 * DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=PocketPayAdminTest
 */
class PocketPayAdminTest extends TestCase
{
    use BuildsPocketPaySchema;

    private User $superAdmin;

    private User $schoolAdmin;

    private Business $school;

    private Business $otherSchool;

    private Student $student;

    protected function setUp(): void
    {
        $this->skipUnlessInMemorySqlite();

        parent::setUp();

        $this->buildSchema();
        $this->fakeProviders();

        $featureId = DB::table('features')->insertGetId(['name' => PocketPayService::FEATURE_NAME, 'created_at' => now(), 'updated_at' => now()]);
        $adminRole = Role::forceCreate(['name' => 'Admin']);

        $quisat = Business::forceCreate(['name' => 'Quisat']);
        $this->school = Business::forceCreate(['name' => 'Hill Primary', 'enabled_feature_ids' => [$featureId]]);
        $this->otherSchool = Business::forceCreate(['name' => 'Lake View', 'enabled_feature_ids' => [$featureId]]);

        $this->superAdmin = User::forceCreate(['name' => 'Quisat Admin', 'email' => 'root@quisat.test', 'business_id' => $quisat->id]);
        $this->schoolAdmin = User::forceCreate([
            'name' => 'Head Teacher', 'email' => 'head@hill.test', 'business_id' => $this->school->id, 'role_id' => $adminRole->id,
        ]);

        $parent = ParentGuardian::forceCreate(['first_name' => 'Ruth', 'last_name' => 'N', 'phone' => '0772000111', 'business_id' => $this->school->id]);
        $this->student = Student::forceCreate([
            'first_name' => 'Amani', 'last_name' => 'N', 'business_id' => $this->school->id, 'parent_guardian_id' => $parent->id,
        ]);
    }

    public function test_super_admin_generates_unique_year_prefixed_card_numbers(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ListPocketPayCardNumbers::class)
            ->callTableAction('generate', data: ['count' => 25, 'batch' => 'Run 1'])
            ->assertHasNoTableActionErrors();

        $numbers = PocketPayCardNumber::pluck('number');
        $this->assertCount(25, $numbers->unique());

        foreach ($numbers as $number) {
            $this->assertMatchesRegularExpression('/^'.now()->format('y').'\d{10}$/', $number);
            $this->assertSame($number, PocketPayCardNumber::withCheckDigit(substr($number, 0, 11)));
        }

        Livewire::test(ListPocketPayCardNumbers::class)
            ->callTableAction('add', data: ['number' => $numbers->first()])
            ->assertHasTableActionErrors(['number']);
    }

    public function test_super_admin_downloads_card_qr_codes(): void
    {
        $this->actingAs($this->superAdmin);
        $numbers = PocketPayCardNumber::generate(3, 'Run 1');
        $first = $numbers->first();

        Livewire::test(ListPocketPayCardNumbers::class)
            ->callTableAction('qr', $first)
            ->assertFileDownloaded("card-{$first->number}.svg");

        Livewire::test(ListPocketPayCardNumbers::class)
            ->callTableAction('download_batch', data: ['batch' => 'Run 1'])
            ->assertFileDownloaded('card-qr-run-1.zip');

        Livewire::test(ListPocketPayCardNumbers::class)
            ->callTableBulkAction('download_qr', $numbers)
            ->assertFileDownloaded('card-qr-codes.zip');

        $svg = \App\Support\PocketPayCardQr::svg($first->number);
        $this->assertStringStartsWith('<?xml', $svg);
        $this->assertStringContainsString(PocketPayCardNumber::format($first->number), $svg);
    }

    public function test_school_admin_cannot_open_card_numbers(): void
    {
        $this->actingAs($this->schoolAdmin)->get(route('pocket-pay.card-numbers.index'))->assertForbidden();
    }

    public function test_super_admin_assigns_a_stock_number_and_it_cannot_be_reused(): void
    {
        $this->actingAs($this->superAdmin);
        [$first, $second] = PocketPayCardNumber::generate(2)->all();

        Livewire::test(ListPocketPayCards::class)
            ->callTableAction('assign', data: [
                'business_id' => $this->school->id,
                'student_id' => $this->student->id,
                'card_number_id' => $first->id,
            ])
            ->assertHasNoTableActionErrors();

        $card = PocketPayCard::firstOrFail();
        $this->assertSame($first->number, $card->card_number);
        $this->assertSame(PocketPayCard::STATUS_INACTIVE, $card->status);
        $this->assertSame(PocketPayCardNumber::STATUS_ASSIGNED, $first->fresh()->status);
        $this->assertSame($card->id, $first->fresh()->pocket_pay_card_id);

        $other = Student::forceCreate(['first_name' => 'Bo', 'last_name' => 'K', 'business_id' => $this->school->id]);

        Livewire::test(ListPocketPayCards::class)
            ->callTableAction('assign', data: [
                'business_id' => $this->school->id,
                'student_id' => $other->id,
                'card_number_id' => $first->id,
            ]);

        $this->assertNull($other->fresh()->pocketPayCard);

        Livewire::test(ListPocketPayCards::class)
            ->callTableAction('change_number', $card, data: ['card_number_id' => $second->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame($second->number, $card->fresh()->card_number);
        $this->assertSame(PocketPayCardNumber::STATUS_RETIRED, $first->fresh()->status);
        $this->assertSame(PocketPayCardNumber::STATUS_ASSIGNED, $second->fresh()->status);
    }

    public function test_school_admin_cannot_assign_cards_and_only_sees_own_school(): void
    {
        $otherStudent = Student::forceCreate(['first_name' => 'Zed', 'last_name' => 'Q', 'business_id' => $this->otherSchool->id]);
        $mine = PocketPayCard::create(['business_id' => $this->school->id, 'student_id' => $this->student->id, 'card_number' => 'HILL1']);
        $theirs = PocketPayCard::create(['business_id' => $this->otherSchool->id, 'student_id' => $otherStudent->id, 'card_number' => 'LAKE1']);

        $this->actingAs($this->schoolAdmin);

        Livewire::test(ListPocketPayCards::class)
            ->assertTableActionHidden('assign')
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_school_without_the_feature_is_refused(): void
    {
        $this->school->forceFill(['enabled_feature_ids' => []])->save();
        $this->actingAs($this->schoolAdmin);

        Livewire::test(ListCanteens::class)->assertForbidden();
    }

    public function test_school_admin_creates_canteen_tops_up_float_and_adds_attendant(): void
    {
        $this->actingAs($this->schoolAdmin);

        Livewire::test(ListCanteens::class)
            ->callTableAction('create', data: ['name' => 'Main Canteen', 'location' => 'Block A', 'low_float_threshold' => 20000])
            ->assertHasNoTableActionErrors();

        $canteen = Canteen::firstOrFail();
        $this->assertSame($this->school->id, $canteen->business_id);

        Livewire::test(ListCanteens::class)->assertTableActionHidden('topup', $canteen);
        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])->assertActionHidden('topup');

        $this->actingAs($this->superAdmin);

        Storage::fake(CanteenFloatTopup::PROOF_DISK);

        Livewire::test(ListCanteens::class)
            ->callTableAction('topup', $canteen, data: [
                'amount' => 100000,
                'paid_on' => now()->toDateString(),
                'payment_reference' => 'RCPT-001',
                'proof' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertHasNoTableActionErrors();

        $topup = CanteenFloatTopup::firstOrFail();
        $this->assertEquals(100000, $canteen->fresh()->float_balance);
        $this->assertSame('RCPT-001', $topup->payment_reference);
        $this->assertSame($this->superAdmin->id, $topup->recorded_by);
        Storage::disk(CanteenFloatTopup::PROOF_DISK)->assertExists($topup->proof_path);
        $this->assertStringContainsString('Ref RCPT-001', $canteen->floatTransactions()->value('description'));
        $this->assertSame(0, PocketPayTopup::count());

        Livewire::test(ListCanteens::class)
            ->callTableAction('topup', $canteen, data: ['amount' => 100000, 'paid_on' => now()->toDateString()])
            ->assertHasTableActionErrors(['proof' => 'required']);

        $this->actingAs($this->schoolAdmin);
        $this->get(route('pocket-pay.float-topups.proof', $topup))->assertOk();

        $theirs = Canteen::create(['business_id' => $this->otherSchool->id, 'name' => 'Their Canteen']);
        $theirTopup = app(PocketPayService::class)->recordFloatTopup($theirs, 5000, $topup->proof_path, $this->superAdmin);
        $this->get(route('pocket-pay.float-topups.proof', $theirTopup))->assertForbidden();

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'attendants')
            ->callAction('addAttendant', data: ['name' => 'Grace', 'phone' => '0700 000 001'])
            ->assertHasNoActionErrors();

        $attendant = CanteenAttendant::firstOrFail();
        $this->assertSame('+256700000001', $attendant->phone);
        $this->assertSame(CanteenAttendant::STATUS_PENDING_SETUP, $attendant->status);
        $this->assertSame('sent', SmsMessage::where('purpose', 'pocket_pay.attendant_otp')->value('status'));
    }

    public function test_school_admin_cannot_open_another_schools_canteen(): void
    {
        $theirs = Canteen::create(['business_id' => $this->otherSchool->id, 'name' => 'Their Canteen']);
        $this->actingAs($this->schoolAdmin);

        Livewire::test(CanteenDetail::class, ['canteen' => $theirs])->assertForbidden();
        $this->get(route('pocket-pay.canteens.report', [$theirs, 'full']))->assertForbidden();
    }

    public function test_canteen_detail_shows_period_figures_tabs_and_pdf_reports(): void
    {
        [$canteen, $attendant, $card] = $this->canteenWithSales();
        $this->actingAs($this->schoolAdmin);

        // The full page needs the app layout's tables; the component carries all of the content.
        $component = Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->assertSee('Main Canteen')
            ->assertSee('Hill Primary')
            ->assertSee('UGX 3,500');

        $summary = $component->instance()->report['summary'];
        $this->assertEquals(3500, $summary['net_sales']);
        $this->assertSame(2, $summary['transactions']);
        $this->assertSame(1, $summary['students_served']);
        $this->assertEquals(10000, $summary['float_topups']);
        $this->assertEquals(0, $summary['float_opening']);
        $this->assertEquals(6500, $summary['float_closing']);

        foreach (CanteenDetail::TABS as $tab) {
            $component->call('setTab', $tab)->assertOk()->assertSet('tab', $tab);
        }

        $component->call('setTab', 'sales')->assertSee('Amani N')->set('search', 'nobody')->assertSee('No sales match');
        $component->call('setTab', 'attendants')->assertSee('Grace');
        $component->call('setTab', 'students')->assertSee($card->maskedNumber());
        $component->call('preset', 'last_month')->assertSee('UGX 0');

        foreach (array_keys(\App\Http\Controllers\PocketPayController::REPORTS) as $type) {
            $response = $this->get(route('pocket-pay.canteens.report', [$canteen, $type]));
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $this->get(route('pocket-pay.canteens.report', [$canteen, 'nope']))->assertNotFound();
    }

    public function test_canteen_detail_manages_attendants(): void
    {
        [$canteen, $attendant] = $this->canteenWithSales();
        $this->actingAs($this->schoolAdmin);

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'attendants')
            ->callAction('disableAttendant', arguments: ['attendant' => $attendant->id]);

        $this->assertSame(CanteenAttendant::STATUS_DISABLED, $attendant->fresh()->status);

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'attendants')
            ->callAction('enableAttendant', arguments: ['attendant' => $attendant->id]);

        $this->assertSame(CanteenAttendant::STATUS_ACTIVE, $attendant->fresh()->status);
    }

    public function test_canteen_detail_deactivates_and_activates_cards(): void
    {
        [$canteen, , $card] = $this->canteenWithSales();
        $this->actingAs($this->schoolAdmin);

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'students')
            ->callAction('deactivateCard', arguments: ['card' => $card->id]);

        $this->assertSame(PocketPayCard::STATUS_BLOCKED, $card->fresh()->status);
        $this->assertEquals(16500, $card->fresh()->balance);

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'students')
            ->assertSee('Deactivated')
            ->callAction('activateCard', arguments: ['card' => $card->id]);

        $this->assertSame(PocketPayCard::STATUS_ACTIVE, $card->fresh()->status);
    }

    /**
     * A canteen topped up with 10,000 and two sales (2,000 + 1,500) by Amani.
     *
     * @return array{0: Canteen, 1: CanteenAttendant, 2: PocketPayCard}
     */
    private function canteenWithSales(): array
    {
        $canteen = Canteen::create(['business_id' => $this->school->id, 'name' => 'Main Canteen', 'location' => 'Block A']);
        $attendant = CanteenAttendant::create(['business_id' => $this->school->id, 'canteen_id' => $canteen->id, 'name' => 'Grace', 'phone' => '+256700000001']);
        $attendant->forceFill(['status' => 'active', 'pin' => bcrypt('1234')])->save();
        $card = PocketPayCard::create(['business_id' => $this->school->id, 'student_id' => $this->student->id, 'card_number' => '260412344821']);

        $service = app(PocketPayService::class);
        $service->activateCard($card, '1111', $attendant);
        $card->forceFill(['balance' => 20000])->save();

        $service->recordFloatTopup($canteen, 10000, 'pocket-pay/float-proofs/receipt.jpg', $this->superAdmin, 'receipt.jpg');

        $service->makeSale($attendant, '260412344821', 2000, '1111');
        $service->makeSale($attendant, '260412344821', 1500, '1111');

        return [$canteen->fresh(), $attendant, $card];
    }

    public function test_only_super_admin_approves_a_pending_reversal(): void
    {
        $canteen = Canteen::create(['business_id' => $this->school->id, 'name' => 'Main Canteen']);
        $attendant = CanteenAttendant::create(['business_id' => $this->school->id, 'canteen_id' => $canteen->id, 'name' => 'Grace', 'phone' => '+256700000001']);
        $attendant->forceFill(['status' => 'active'])->save();
        $card = PocketPayCard::create(['business_id' => $this->school->id, 'student_id' => $this->student->id, 'card_number' => 'HILL1']);

        $service = app(PocketPayService::class);
        $service->activateCard($card, '1111', $attendant);
        $card->forceFill(['balance' => 5000])->save();
        $canteen->forceFill(['float_balance' => 5000])->save();
        $sale = $service->makeSale($attendant, 'HILL1', 2000, '1111');
        $service->requestReversal($attendant, $sale, 'Wrong amount keyed');

        $this->actingAs($this->schoolAdmin);
        $this->get(route('pocket-pay.reversals.index'))->assertForbidden();

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'reversals')
            ->assertSee($sale->receipt_number)
            ->assertActionHidden('approveReversal', ['sale' => $sale->id])
            ->assertActionHidden('rejectReversal', ['sale' => $sale->id]);

        $this->assertSame(CanteenSale::STATUS_COMPLETED, $sale->fresh()->status);

        $this->actingAs($this->superAdmin);

        Livewire::test(ListCanteenSales::class, ['reversalsOnly' => true])->assertCanSeeTableRecords([$sale]);

        Livewire::test(CanteenDetail::class, ['canteen' => $canteen])
            ->call('setTab', 'reversals')
            ->assertSee($sale->receipt_number)
            ->callAction('approveReversal', data: ['note' => 'ok'], arguments: ['sale' => $sale->id])
            ->assertHasNoActionErrors();

        $this->assertSame(CanteenSale::STATUS_REVERSED, $sale->fresh()->status);
        $this->assertEquals(5000, $card->fresh()->balance);
        $this->assertSame($this->superAdmin->id, $sale->fresh()->reversal_reviewed_by);
    }

    public function test_super_admin_sees_the_marzsms_log(): void
    {
        $this->actingAs($this->superAdmin);
        $sms = app(\App\Services\MarzSmsService::class)->sendNow('0772000111', 'Hello', 'test', $this->school);

        Livewire::test(\App\Livewire\MarzSms\ListSmsMessages::class)
            ->assertCanSeeTableRecords([$sms])
            ->filterTable('status', 'sent')
            ->assertCanSeeTableRecords([$sms]);
    }
}
