<?php

namespace Tests\Feature;

use App\Livewire\PocketPay\CanteenAttendants;
use App\Livewire\PocketPay\ListCanteens;
use App\Livewire\PocketPay\ListCanteenSales;
use App\Livewire\PocketPay\ListPocketPayCardNumbers;
use App\Livewire\PocketPay\ListPocketPayCards;
use App\Models\Business;
use App\Models\Canteen;
use App\Models\CanteenAttendant;
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
use Illuminate\Support\Facades\DB;
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

        Livewire::test(ListCanteens::class)
            ->callTableAction('topup', $canteen, data: ['amount' => 100000, 'phone_number' => '0701000222'])
            ->assertHasNoTableActionErrors();

        $topup = PocketPayTopup::firstOrFail();
        $this->assertSame(Canteen::class, $topup->topupable_type);
        $this->assertSame('processing', $topup->status);

        Livewire::test(CanteenAttendants::class, ['canteen' => $canteen])
            ->callTableAction('add', data: ['name' => 'Grace', 'phone' => '0700 000 001'])
            ->assertHasNoTableActionErrors();

        $attendant = CanteenAttendant::firstOrFail();
        $this->assertSame('+256700000001', $attendant->phone);
        $this->assertSame(CanteenAttendant::STATUS_PENDING_SETUP, $attendant->status);
        $this->assertSame('sent', SmsMessage::where('purpose', 'pocket_pay.attendant_otp')->value('status'));
    }

    public function test_school_admin_cannot_open_another_schools_canteen(): void
    {
        $theirs = Canteen::create(['business_id' => $this->otherSchool->id, 'name' => 'Their Canteen']);
        $this->actingAs($this->schoolAdmin);

        Livewire::test(CanteenAttendants::class, ['canteen' => $theirs])->assertForbidden();
    }

    public function test_school_admin_approves_a_pending_reversal(): void
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

        Livewire::test(ListCanteenSales::class, ['reversalsOnly' => true])
            ->assertCanSeeTableRecords([$sale])
            ->callTableAction('approve', $sale, data: ['note' => 'ok'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(CanteenSale::STATUS_REVERSED, $sale->fresh()->status);
        $this->assertEquals(5000, $card->fresh()->balance);
        $this->assertSame($this->schoolAdmin->id, $sale->fresh()->reversal_reviewed_by);
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
