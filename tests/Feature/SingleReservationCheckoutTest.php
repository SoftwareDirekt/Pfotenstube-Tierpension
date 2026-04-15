<?php

namespace Tests\Feature;

use App\Helpers\VATCalculator;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Dog;
use App\Models\Plan;
use App\Models\Preference;
use App\Models\Reservation;
use App\Models\ReservationGroup;
use App\Models\ReservationPayment;
use App\Models\ReservationPaymentEntry;
use App\Models\Room;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Feature tests for {@see \App\Http\Controllers\ReservationsController::checkout}
 * (single-reservation Kasse checkout only — not bulk dogs-in-rooms checkout).
 *
 * Covers realistic timing (same-day, on-time, early, late) and payment patterns
 * (none, partial advance, full advance, overpay at desk, early overpayment correction).
 */
class SingleReservationCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected Customer $customer;

    protected Dog $dog;

    protected Room $room;

    protected Plan $dailyPlan;

    protected Plan $flatPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-20 14:00:00', 'Europe/Vienna'));

        $this->withoutMiddleware([
            \App\Http\Middleware\AdminAuthenticated::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        Preference::updateOrCreate(
            ['key' => 'vat_percentage'],
            ['value' => '20', 'type' => 'integer']
        );

        $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldReceive('generateCheckoutInvoice')->zeroOrMoreTimes()->andReturn([
                'success' => true,
                'invoice_id' => null,
                'invoice_number' => 'TEST',
                'invoice_pdf_base64' => '',
                'file_path' => null,
            ]);
            $mock->shouldReceive('generateInvoice')->zeroOrMoreTimes()->andReturn([
                'success' => true,
                'invoice_id' => null,
            ]);
        });

        $this->admin = Admin::factory()->create([
            'email' => 'checkout-test-admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        $this->customer = Customer::factory()->create();
        $this->dog = Dog::factory()->create(['customer_id' => $this->customer->id]);
        $this->room = Room::factory()->create();

        $this->dailyPlan = Plan::factory()->create([
            'title' => 'Tagestarif Test',
            'type' => 'daily',
            'price' => 50.00,
            'flat_rate' => 0,
            'discount' => 0,
        ]);

        $this->flatPlan = Plan::factory()->create([
            'title' => 'Pauschal Test',
            'type' => 'flat',
            'price' => 200.00,
            'flat_rate' => 1,
            'discount' => 0,
        ]);

        // Default guard is "web"; checkout / invoice code uses Auth::user() without a guard.
        Auth::shouldUse('admin');
        $this->actingAs($this->admin, 'admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Auth::shouldUse('web');
        parent::tearDown();
    }

    /** Gross total as computed by checkout (exclusive VAT, percentage discount on net). */
    protected function expectedGrossExclusive(
        float $planNetTotal,
        float $additionalNetTotal = 0.0,
        int $discountPercent = 0
    ): float {
        $base = $planNetTotal + $additionalNetTotal;
        $netTotal = round($base * (1 - ($discountPercent / 100)), 2);
        $vatAmount = VATCalculator::calculateVATAmount($netTotal, 20.0);

        return round($netTotal + $vatAmount, 2);
    }

    /** Same as controller branch for inclusive + 0% discount (sticker = gross). */
    protected function expectedGrossInclusiveSticker(float $planPlusExtrasGross): float
    {
        return round($planPlusExtrasGross, 2);
    }

    protected function postCheckout(Reservation $reservation, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        $checkoutStr = ($overrides['checkout'] ?? null) instanceof Carbon
            ? $overrides['checkout']->format('d.m.Y')
            : ($overrides['checkout'] ?? Carbon::now()->format('d.m.Y'));

        unset($overrides['checkout']);

        $payload = array_merge([
            'id' => $reservation->id,
            'received_amount' => 0,
            'total' => 0,
            'discount' => 0,
            'gateway' => 'Bar',
            'checkout' => $checkoutStr,
            'price_plan' => $reservation->plan_id,
        ], $overrides);

        if (! array_key_exists('total', $overrides) || $overrides['total'] === 0) {
            $reservation->refresh();
            $reservation->load('plan');
            $checkin = $reservation->checkin_date
                ? Carbon::parse($reservation->checkin_date)->startOfDay()
                : null;
            $checkoutParsed = Carbon::createFromFormat('d.m.Y', is_string($payload['checkout']) ? $payload['checkout'] : Carbon::now()->format('d.m.Y'));
            $daysDiff = $checkin ? $checkin->diffInDays($checkoutParsed->copy()->startOfDay()) : 0;
            $calculationMode = config('app.days_calculation_mode', 'inclusive');
            $days = ($daysDiff === 0) ? 1 : (($calculationMode === 'inclusive') ? $daysDiff + 1 : $daysDiff);
            $plan = Plan::find((int) ($payload['price_plan'] ?? $reservation->plan_id));
            $isFlat = $plan && (int) $plan->flat_rate === 1;
            $planNet = $isFlat ? (float) $plan->price : (float) $plan->price * $days;
            $payload['total'] = $this->expectedGrossExclusive(
                $planNet,
                0.0,
                $isFlat ? 0 : (int) $payload['discount']
            );
        }

        return $this->post(route('admin.reservation.checkout'), $payload);
    }

    protected function attachAdvancePayment(Reservation $reservation, float $amount, string $type = 'advance'): ReservationPayment
    {
        $header = ReservationPayment::firstOrCreate(
            ['res_id' => $reservation->id],
            ['total_due' => 999, 'status' => 'partial']
        );

        ReservationPaymentEntry::create([
            'res_payment_id' => $header->id,
            'amount' => $amount,
            'method' => 'Bar',
            'type' => $type,
            'transaction_date' => Carbon::now()->subDay(),
            'status' => 'active',
        ]);

        return $header;
    }

    protected function assertMoneyEquals(float $expected, float $actual, string $message = ''): void
    {
        $this->assertEqualsWithDelta($expected, $actual, 0.02, $message);
    }

    // ─── Timing: same-day, on-time, late ─────────────────────────────────

    public function test_checkout_same_day_as_check_in_full_payment_closes_stay(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $day = Carbon::now()->startOfDay();
        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $day,
            'checkout_date' => $day->copy()->addDays(3),
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(50.0, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $day->copy()->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])->assertRedirect();

        $res->refresh();
        $this->assertEquals(Reservation::STATUS_CHECKED_OUT, $res->status);
        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($gross, (float) $header->total_due);
        $this->assertSame('paid', $header->status);
        $this->assertMoneyEquals($gross, (float) $header->entries()->where('status', 'active')->sum('amount'));
    }

    public function test_checkout_on_planned_last_day_multi_day_stay_full_payment(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $plannedCheckout = Carbon::parse('2026-06-14')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $plannedCheckout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $daysDiff = $checkin->diffInDays($plannedCheckout);
        $days = $daysDiff + 1;
        $gross = $this->expectedGrossExclusive(50.0 * $days, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $plannedCheckout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])->assertRedirect();

        $res->refresh();
        $this->assertEquals(Reservation::STATUS_CHECKED_OUT, $res->status);
        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('paid', $header->status);
        $this->assertMoneyEquals($gross, (float) $header->total_due);
    }

    public function test_checkout_after_planned_date_extra_nights_raise_the_total(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $planned = Carbon::parse('2026-06-12')->startOfDay();
        $actual = Carbon::parse('2026-06-14')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $planned,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $days = $checkin->diffInDays($actual) + 1;
        $gross = $this->expectedGrossExclusive(50.0 * $days, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($gross, (float) $header->total_due);
        $this->assertSame('paid', $header->status);
    }

    public function test_checkout_before_planned_date_lowers_the_total_early_departure(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $planned = Carbon::parse('2026-06-16')->startOfDay();
        $actual = Carbon::parse('2026-06-12')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $planned,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $daysShort = $checkin->diffInDays($actual) + 1;
        $grossShort = $this->expectedGrossExclusive(50.0 * $daysShort, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => $grossShort,
            'total' => $grossShort,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($grossShort, (float) $header->total_due);
        $this->assertSame('paid', $header->status);
    }

    // ─── Payments: no advance, partial advance, full advance, overpay ─────

    public function test_first_payment_at_checkout_partial_amount_leaves_partial_status(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);
        $pay = round($gross / 2, 2);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $pay,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('partial', $header->status);
        $this->assertMoneyEquals($pay, (float) $header->entries()->where('status', 'active')->sum('amount'));
        $this->assertMoneyEquals($gross, (float) $header->total_due);
    }

    public function test_partial_advance_on_file_final_payment_at_checkout_covers_the_rest(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);
        $this->attachAdvancePayment($res, 60.0);
        $remainder = round($gross - 60.0, 2);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $remainder,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('paid', $header->status);
        $this->assertTrue(
            $header->entries()->where('status', 'active')->where('type', 'final')->exists()
        );
        $this->assertMoneyEquals($gross, (float) $header->entries()->where('status', 'active')->sum('amount'));
    }

    public function test_advance_already_covers_full_total_no_cash_needed_at_checkout(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);
        $this->attachAdvancePayment($res, $gross);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => 0,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('paid', $header->status);
        $this->assertEquals(1, $header->entries()->where('status', 'active')->count());
    }

    public function test_balance_zero_customer_pays_extra_at_desk_stored_as_new_advance(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);
        $this->attachAdvancePayment($res, $gross);
        $extra = 25.0;

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $extra,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertTrue(
            $header->entries()->where('status', 'active')->where('type', 'advance')->where('amount', '>=', $extra - 0.05)->exists()
        );
        $this->assertMoneyEquals($gross + $extra, (float) $header->entries()->where('status', 'active')->sum('amount'));
    }

    public function test_customer_overpays_at_desk_creates_final_line_and_advance_credit(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);
        $over = 40.0;

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross + $over,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('paid', $header->status);
        $this->assertTrue($header->entries()->where('status', 'active')->where('type', 'final')->exists());
        $this->assertTrue($header->entries()->where('status', 'active')->where('type', 'advance')->exists());
        $this->assertMoneyEquals($gross + $over, (float) $header->entries()->where('status', 'active')->sum('amount'));
    }

    // ─── Early checkout + advances (controller early branch) ─────────────

    public function test_early_departure_advance_overpaid_replaces_history_with_one_corrective_line(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $planned = Carbon::parse('2026-06-16')->startOfDay();
        $actual = Carbon::parse('2026-06-12')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $planned,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $daysShort = $checkin->diffInDays($actual) + 1;
        $grossShort = $this->expectedGrossExclusive(50.0 * $daysShort, 0.0, 0);
        $daysLong = $checkin->diffInDays($planned) + 1;
        $paidLong = $this->expectedGrossExclusive(50.0 * $daysLong, 0.0, 0);

        $this->attachAdvancePayment($res, $paidLong);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => 0,
            'total' => $grossShort,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($grossShort, (float) $header->total_due);
        $active = $header->entries()->where('status', 'active')->get();
        $this->assertCount(1, $active);
        $this->assertSame('final', $active->first()->type);
        $this->assertMoneyEquals($grossShort, (float) $active->first()->amount);
        $this->assertMoneyEquals(round($paidLong - $grossShort, 2), (float) ($active->first()->overpaid_amount ?? 0));
    }

    public function test_early_departure_partial_advance_customer_settles_remaining_at_desk(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $planned = Carbon::parse('2026-06-15')->startOfDay();
        $actual = Carbon::parse('2026-06-12')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $planned,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $daysShort = $checkin->diffInDays($actual) + 1;
        $grossShort = $this->expectedGrossExclusive(50.0 * $daysShort, 0.0, 0);
        $advance = 100.0;
        $this->attachAdvancePayment($res, $advance);
        $remainder = round($grossShort - $advance, 2);

        $this->assertGreaterThan(0.02, $remainder);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => $remainder,
            'total' => $grossShort,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('paid', $header->status);
        $this->assertTrue($header->entries()->where('status', 'active')->where('type', 'final')->exists());
    }

    public function test_early_departure_partial_advance_no_desk_payment_stays_partially_unpaid(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $planned = Carbon::parse('2026-06-15')->startOfDay();
        $actual = Carbon::parse('2026-06-12')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $planned,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $daysShort = $checkin->diffInDays($actual) + 1;
        $grossShort = $this->expectedGrossExclusive(50.0 * $daysShort, 0.0, 0);
        $advance = 80.0;
        $this->attachAdvancePayment($res, $advance);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => 0,
            'total' => $grossShort,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertTrue(in_array($header->status, ['partial', 'unpaid'], true));
        $this->assertMoneyEquals($advance, (float) $header->entries()->where('status', 'active')->sum('amount'));
    }

    public function test_late_departure_partial_advance_customer_pays_additional_balance(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $planned = Carbon::parse('2026-06-12')->startOfDay();
        $actual = Carbon::parse('2026-06-14')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $planned,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $daysLate = $checkin->diffInDays($actual) + 1;
        $grossLate = $this->expectedGrossExclusive(50.0 * $daysLate, 0.0, 0);
        $this->attachAdvancePayment($res, 120.0);
        $remainder = round($grossLate - 120.0, 2);
        $this->assertGreaterThan(0.02, $remainder);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => $remainder,
            'total' => $grossLate,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertSame('paid', $header->status);
    }

    // ─── Plan / VAT variants ─────────────────────────────────────────────

    public function test_flat_rate_plan_charges_one_fixed_price_even_for_longer_stay(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-16')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->flatPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(200.0, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
            'price_plan' => $this->flatPlan->id,
            'discount' => 0,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($gross, (float) $header->total_due);
    }

    public function test_vat_inclusive_list_prices_without_discount_match_checkout_gross(): void
    {
        config(['app.vat_calculation_mode' => 'inclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossInclusiveSticker(100.0);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($gross, (float) $header->total_due);
    }

    public function test_ten_percent_discount_reduces_the_checkout_total(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 10);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
            'discount' => 10,
        ])->assertRedirect();

        $header = ReservationPayment::where('res_id', $res->id)->firstOrFail();
        $this->assertMoneyEquals($gross, (float) $header->total_due);
    }

    // ─── Guards & validation ─────────────────────────────────────────────

    public function test_group_booking_cannot_use_single_checkout_shows_error_message(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $group = ReservationGroup::create([
            'customer_id' => $this->customer->id,
            'total_due' => 100,
            'status' => 'partial',
        ]);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
            'reservation_group_id' => $group->id,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $res->refresh();
        $this->assertNotEquals(Reservation::STATUS_CHECKED_OUT, $res->status);
    }

    public function test_second_checkout_attempt_is_rejected(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])->assertRedirect();

        $this->postCheckout($res, [
            'checkout' => $checkout->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_checkout_date_before_check_in_is_rejected(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-15')->startOfDay();
        $checkout = Carbon::parse('2026-06-20')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $this->postCheckout($res, [
            'checkout' => $checkin->copy()->subDay()->format('d.m.Y'),
            'received_amount' => 0,
            'total' => 60,
        ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_checkout_date_in_the_future_is_rejected(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $checkout = Carbon::parse('2026-06-25')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $future = Carbon::now()->addDays(5)->format('d.m.Y');

        $this->post(route('admin.reservation.checkout'), [
            'id' => $res->id,
            'received_amount' => 0,
            'total' => 100,
            'discount' => 0,
            'gateway' => 'Bar',
            'checkout' => $future,
            'price_plan' => $this->dailyPlan->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_checkout_succeeds_when_reservation_has_no_planned_check_out_date(): void
    {
        config(['app.vat_calculation_mode' => 'exclusive', 'app.days_calculation_mode' => 'inclusive']);

        $checkin = Carbon::parse('2026-06-10')->startOfDay();
        $actual = Carbon::parse('2026-06-11')->startOfDay();

        $res = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->dailyPlan->id,
            'checkin_date' => $checkin,
            'checkout_date' => null,
            'status' => Reservation::STATUS_ACTIVE,
        ]);

        $gross = $this->expectedGrossExclusive(100.0, 0.0, 0);

        $this->postCheckout($res, [
            'checkout' => $actual->format('d.m.Y'),
            'received_amount' => $gross,
            'total' => $gross,
        ])->assertRedirect();

        $res->refresh();
        $this->assertEquals(Reservation::STATUS_CHECKED_OUT, $res->status);
    }
}
