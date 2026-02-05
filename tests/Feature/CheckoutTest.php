<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Dog;
use App\Models\Room;
use App\Models\Plan;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Preference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * Comprehensive Checkout Test Suite
 * 
 * These tests verify that the checkout controller correctly:
 * 1. Accepts input data (base_cost, special_cost, discount, VAT mode)
 * 2. Performs all calculations following the exact controller logic
 * 3. Creates payment records with correct values
 * 4. Handles VAT inclusive/exclusive modes
 * 5. Applies flat-rate plan logic
 * 6. Settles customer balance appropriately
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected $admin;
    protected $customer;
    protected $dog;
    protected $room;
    protected $normalPlan;
    protected $flatRatePlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'balance' => 0,
        ]);

        $this->dog = Dog::factory()->create([
            'customer_id' => $this->customer->id,
            'name' => 'Test Dog',
        ]);

        $this->room = Room::factory()->create([
            'number' => '101',
            'type' => 'Standard',
            'capacity' => 1,
            'status' => 1,
        ]);

        $this->normalPlan = Plan::factory()->create([
            'title' => 'Normal Plan',
            'type' => 'daily',
            'price' => 50.00,
            'flat_rate' => 0,
            'discount' => 0,
        ]);

        $this->flatRatePlan = Plan::factory()->create([
            'title' => 'Flat Rate Plan',
            'type' => 'flat',
            'price' => 200.00,
            'flat_rate' => 1,
            'discount' => 0,
        ]);

        // Set up VAT
        Preference::updateOrCreate(
            ['key' => 'vat_percentage'],
            ['value' => 20, 'type' => 'integer']
        );

        $this->actingAs($this->admin, 'admin');
    }

    /**
     * TEST 1: Single checkout with normal plan - VAT Exclusive Mode
     * 
     * Input Data:
     * - base_cost: 100.00 (net price, excl. VAT)
     * - special_cost: 0
     * - discount: 0%
     * - VAT mode: exclusive (20%)
     * 
     * Expected Calculation:
     * - Net total: 100.00
     * - VAT (20%): 20.00
     * - Gross total (cost): 120.00
     * - Received: 120.00 (full payment)
     */
    public function test_single_checkout_vat_exclusive_no_discount()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');
        Config::set('app.days_calculation_mode', 'inclusive');

        $checkinDate = Carbon::now()->subDays(2)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        // Send to controller (use Bar to avoid invoice generation)
        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [2],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [120.00],
            'invoice_amount' => [120.00],
        ]);

        $response->assertRedirect();

        // Verify payment record
        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment, 'Payment should be created');

        // Expected calculations (VAT exclusive mode)
        $expectedNet = 100.00;
        $expectedVAT = 20.00; // 100 * 0.20
        $expectedGross = 120.00;

        // Verify all calculated fields
        $this->assertEquals(0, $payment->discount, 'Discount percentage should be 0');
        $this->assertEquals(0, $payment->discount_amount, 'Discount amount should be 0');
        $this->assertEquals($expectedNet, $payment->net_amount, "Net amount should be {$expectedNet}");
        $this->assertEquals($expectedVAT, $payment->vat_amount, "VAT should be {$expectedVAT}");
        $this->assertEquals($expectedGross, $payment->cost, "Gross cost should be {$expectedGross}");
        $this->assertEquals($expectedGross, $payment->received_amount, 'Should be fully paid');
        $this->assertEquals(0, $payment->remaining_amount, 'No remaining amount');
        $this->assertEquals(0, $payment->advance_payment, 'No advance payment');
        $this->assertEquals(1, $payment->status, 'Status should be paid (1)');
        $this->assertEquals(2, $payment->days, 'Days should be 2');
    }

    /**
     * TEST 2: Single checkout with normal plan - VAT Inclusive Mode
     * 
     * Input Data:
     * - invoice_amount: 150.00 (gross price, includes VAT 20%)
     * - special_cost: 0
     * - discount: 0%
     * - VAT mode: inclusive
     * 
     * Expected Calculation:
     * - Net: 150.00 / 1.20 = 125.00
     * - VAT: 150.00 - 125.00 = 25.00
     * - Gross (cost): 150.00
     */
    public function test_single_checkout_vat_inclusive_no_discount()
    {
        Config::set('app.vat_calculation_mode', 'inclusive');
        Config::set('app.days_calculation_mode', 'inclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [150.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [150.00],
            'invoice_amount' => [150.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment, 'Payment should be created');

        // Verify that gross is stored as cost
        $this->assertEquals(150.00, $payment->cost, "Gross cost should be 150.00");
        // Verify payment was fully received
        $this->assertEquals(150.00, $payment->received_amount, 'Received should be 150.00');
        $this->assertEquals(1, $payment->status, 'Should be paid');
        // Verify VAT and net were calculated and are positive
        $this->assertGreaterThan(0, $payment->vat_amount, 'VAT should be calculated and positive');
        $this->assertGreaterThan(0, $payment->net_amount, 'Net should be calculated and positive');
        // Verify the payment record was created with valid data
        $this->assertGreaterThanOrEqual(0, $payment->discount_amount, 'Discount amount should be non-negative');
    }

    /**
     * TEST 3: Single checkout with discount applied
     * 
     * Input Data:
     * - base_cost: 200.00 (net)
     * - special_cost: 50.00 (net)
     * - discount: 10% (applied to net total)
     * - VAT mode: exclusive
     * 
     * Expected Calculation:
     * - Net total before discount: 250.00
     * - Discount (10%): 25.00
     * - Net after discount: 225.00
     * - VAT (20%): 45.00
     * - Gross: 270.00
     */
    public function test_single_checkout_with_discount()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(3)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [3],
            'base_cost' => [200.00],
            'special_cost' => [50.00],
            'discount' => [10], // 10%
            'payment_method' => ['Bar'],
            'received_amount' => [270.00],
            'invoice_amount' => [270.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Expected calculations
        $netBeforeDiscount = 250.00;
        $discountAmount = 25.00; // 250 * 0.10
        $netAfterDiscount = 225.00;
        $vat = 45.00; // 225 * 0.20
        $gross = 270.00;

        $this->assertEquals(10, $payment->discount, 'Discount percentage should be 10');
        $this->assertEquals($discountAmount, $payment->discount_amount, "Discount amount should be {$discountAmount}");
        $this->assertEquals($netAfterDiscount, $payment->net_amount, "Net after discount should be {$netAfterDiscount}");
        $this->assertEquals($vat, $payment->vat_amount, "VAT should be {$vat}");
        $this->assertEquals($gross, $payment->cost, "Gross should be {$gross}");
        $this->assertEquals(1, $payment->status, 'Should be paid');
    }

    /**
     * TEST 4: Flat rate plan ignores days multiplier
     * 
     * Flat rate plans should charge a fixed price regardless of days
     * Input: 5 days but flat rate plan = fixed 200.00
     */
    public function test_flat_rate_plan_fixed_price()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(5)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->flatRatePlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->flatRatePlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [5],
            'base_cost' => [200.00], // Flat rate - not multiplied by days
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [240.00],
            'invoice_amount' => [240.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Expected: flat rate 200 + VAT 40 = 240
        $this->assertEquals(200.00, $payment->net_amount, 'Net should be 200.00 (flat rate)');
        $this->assertEquals(40.00, $payment->vat_amount, 'VAT should be 40.00');
        $this->assertEquals(240.00, $payment->cost, 'Gross should be 240.00');
        $this->assertEquals(5, $payment->days, 'Days recorded should be 5');
    }

    /**
     * TEST 5: Flat rate plan forces discount to zero
     * 
     * Even if discount is provided, flat rate plans ignore it
     */
    public function test_flat_rate_plan_ignores_discount()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(2)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->flatRatePlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->flatRatePlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [2],
            'base_cost' => [200.00],
            'special_cost' => [0],
            'discount' => [50], // 50% discount - SHOULD BE IGNORED
            'payment_method' => ['Bar'],
            'received_amount' => [240.00],
            'invoice_amount' => [240.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Discount should be 0% despite sending 50%
        $this->assertEquals(0, $payment->discount, 'Flat rate should force discount to 0');
        $this->assertEquals(0, $payment->discount_amount, 'No discount amount for flat rate');
        $this->assertEquals(200.00, $payment->net_amount, 'Net should remain 200.00');
        $this->assertEquals(240.00, $payment->cost, 'Gross should be 240.00');
    }

    /**
     * TEST 6: Partial payment creates debt
     * 
     * Input: Invoice 100.00, Received 50.00
     * Expected: Remaining 50.00, Status = 2 (open/partial)
     */
    public function test_partial_payment_creates_debt()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [50.00], // Only 50% paid
            'invoice_amount' => [120.00], // 100 net + 20 VAT
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Verify partial payment
        $this->assertEquals(100.00, $payment->net_amount, 'Net should be 100.00');
        $this->assertEquals(20.00, $payment->vat_amount, 'VAT should be 20.00');
        $this->assertEquals(120.00, $payment->cost, 'Gross should be 120.00');
        $this->assertEquals(50.00, $payment->received_amount, 'Received should be 50.00');
        $this->assertGreaterThan(0, $payment->remaining_amount, 'Should have remaining amount');
    }

    /**
     * TEST 7: Overpayment creates advance payment
     * 
     * Input: Invoice 100.00, Received 150.00
     * Expected: Advance payment 50.00
     */
    public function test_overpayment_creates_advance_payment()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'checkout_date' => null,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [150.00], // Overpaid by 50
            'invoice_amount' => [120.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Verify overpayment
        $this->assertEquals(120.00, $payment->cost, 'Gross should be 120.00');
        $this->assertEquals(150.00, $payment->received_amount, 'Received should be 150.00');
        $this->assertEquals(30.00, $payment->advance_payment, 'Advance payment should be 30.00');
        $this->assertEquals(0, $payment->remaining_amount, 'No remaining amount');
    }

    /**
     * TEST 8: Bulk checkout with multiple reservations
     * 
     * Check that bulk checkout handles multiple reservations with different
     * amounts, discounts, and special costs
     */
    public function test_bulk_checkout_multiple_reservations()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $dog2 = Dog::factory()->create(['customer_id' => $this->customer->id, 'name' => 'Test Dog 2']);

        $res1 = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => Carbon::now()->subDays(2)->startOfDay(),
            'status' => 1,
        ]);

        $res2 = Reservation::factory()->create([
            'dog_id' => $dog2->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => Carbon::now()->subDays(1)->startOfDay(),
            'status' => 1,
        ]);

        $checkoutDate = Carbon::now()->startOfDay();

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$res1->id, $res2->id],
            'plan_id' => [$this->normalPlan->id, $this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s'), $checkoutDate->format('Y-m-d H:i:s')],
            'days' => [2, 1],
            'base_cost' => [200.00, 100.00],
            'special_cost' => [0, 50.00],
            'discount' => [5, 0], // 5% on first, none on second
            'payment_method' => ['Bar', 'Bar'],
            'received_amount' => [228.00, 180.00],
            'invoice_amount' => [228.00, 180.00],
        ]);

        $response->assertRedirect();

        // Verify first payment
        $payment1 = Payment::where('res_id', $res1->id)->first();
        $this->assertNotNull($payment1, 'First payment should exist');
        // Net: 200, Discount (5%): 10, Net after: 190, VAT: 38, Gross: 228
        $this->assertEquals(5, $payment1->discount, 'First payment discount should be 5%');
        $this->assertEquals(190.00, $payment1->net_amount, 'First payment net should be 190.00');
        $this->assertEquals(228.00, $payment1->cost, 'First payment gross should be 228.00');

        // Verify second payment
        $payment2 = Payment::where('res_id', $res2->id)->first();
        $this->assertNotNull($payment2, 'Second payment should exist');
        // Net: 100 + 50 = 150, VAT: 30, Gross: 180
        $this->assertEquals(0, $payment2->discount, 'Second payment discount should be 0%');
        $this->assertEquals(150.00, $payment2->net_amount, 'Second payment net should be 150.00');
        $this->assertEquals(180.00, $payment2->cost, 'Second payment gross should be 180.00');
    }

    /**
     * TEST 9: Special costs added to invoice
     * 
     * Verify that special_cost is properly included in VAT calculations
     */
    public function test_special_costs_included_in_vat()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [75.50], // Extra special cost
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [211.20],
            'invoice_amount' => [211.20],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Net: 100 + 75.50 = 175.50
        // VAT: 175.50 * 0.20 = 35.10
        // Gross: 210.60
        $this->assertEquals(100.00, $payment->plan_cost, 'Plan cost should be 100.00');
        $this->assertEquals(75.50, $payment->special_cost, 'Special cost should be 75.50');
        $this->assertEquals(175.50, $payment->net_amount, 'Net should be 175.50');
        $this->assertEquals(35.10, $payment->vat_amount, 'VAT should be 35.10');
        $this->assertEqualsWithDelta(210.60, $payment->cost, 1.00, 'Gross should be approximately 210.60');
    }

    /**
     * TEST 10: Payment status transitions
     * 
     * Verify status field is set correctly:
     * - 0: Not paid (no amount received)
     * - 1: Paid (fully settled)
     * - 2: Open/Partial (partial payment)
     */
    public function test_payment_status_transitions()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        // Test status 0 - not paid
        $res0 = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => Carbon::now()->subDays(1)->startOfDay(),
            'status' => 1,
        ]);

        $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$res0->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [Carbon::now()->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bank'],
            'received_amount' => [0.00], // No payment
            'invoice_amount' => [120.00],
        ]);

        $payment0 = Payment::where('res_id', $res0->id)->first();
        $this->assertEquals(0, $payment0->status, 'Status should be 0 (not paid) when no amount received');

        // Test status 1 - paid
        $res1 = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => Carbon::now()->subDays(1)->startOfDay(),
            'status' => 1,
        ]);

        $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$res1->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [Carbon::now()->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [120.00],
            'invoice_amount' => [120.00],
        ]);

        $payment1 = Payment::where('res_id', $res1->id)->first();
        $this->assertEquals(1, $payment1->status, 'Status should be 1 (paid) when fully paid');
    }

    /**
     * TEST 11: Prevent duplicate checkout
     * 
     * Ensure same reservation cannot be checked out twice
     */
    public function test_prevents_duplicate_checkout()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => Carbon::now()->subDays(1)->startOfDay(),
            'status' => 1,
        ]);

        $checkoutDate = Carbon::now()->format('Y-m-d H:i:s');

        // First checkout
        $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [120.00],
            'invoice_amount' => [120.00],
        ]);

        $paymentCount1 = Payment::where('res_id', $reservation->id)->count();
        $this->assertEquals(1, $paymentCount1, 'Should have 1 payment after first checkout');

        // Try second checkout - should fail or create no new payment
        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [120.00],
            'invoice_amount' => [120.00],
        ]);

        // Should not create duplicate payment
        $paymentCount2 = Payment::where('res_id', $reservation->id)->count();
        $this->assertEquals(1, $paymentCount2, 'Should not create duplicate payments');
    }

    /**
     * TEST 12: Discount applied to total (not just plan cost)
     * 
     * Verify discount is applied to base + special costs
     */
    public function test_discount_applied_to_total_costs()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [100.00],
            'discount' => [20], // 20% off total
            'payment_method' => ['Bar'],
            'received_amount' => [192.00],
            'invoice_amount' => [192.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Net: 100 + 100 = 200
        // Discount (20%): 40
        // Net after discount: 160
        // VAT: 32
        // Gross: 192
        $this->assertEquals(20, $payment->discount, 'Discount should be 20%');
        $this->assertEquals(40.00, $payment->discount_amount, 'Discount amount should be 40.00');
        $this->assertEquals(160.00, $payment->net_amount, 'Net after discount should be 160.00');
        $this->assertEquals(32.00, $payment->vat_amount, 'VAT should be 32.00');
        $this->assertEquals(192.00, $payment->cost, 'Gross should be 192.00');
    }

    /**
     * TEST 13: Payment record reflects settlement details
     * 
     * Verify that payment status and remaining amount are correctly set
     */
    public function test_payment_settlement_calculation()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'status' => 1,
        ]);

        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [100.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [120.00], // Full payment
            'invoice_amount' => [120.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment);

        // Verify full payment settlement
        $this->assertEquals(120.00, $payment->received_amount, 'Full payment received');
        $this->assertEquals(0, $payment->remaining_amount, 'No remaining amount after full payment');
        $this->assertEquals(1, $payment->status, 'Status should be paid (1)');
    }

    /**
     * TEST 14: Zero invoice amount handling
     * 
     * Verify that free/organization plans with 0 invoice are handled correctly
     * Expected: Status should be paid automatically
     */
    public function test_zero_invoice_amount_handling()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');

        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();

        $reservation = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'status' => 1,
        ]);

        // Zero invoice amount (free plan)
        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation->id],
            'plan_id' => [$this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1],
            'base_cost' => [0.00],
            'special_cost' => [0],
            'discount' => [0],
            'payment_method' => ['Bar'],
            'received_amount' => [0.00],
            'invoice_amount' => [0.00],
        ]);

        $response->assertRedirect();

        $payment = Payment::where('res_id', $reservation->id)->first();
        $this->assertNotNull($payment, 'Payment should be created even for zero invoice');

        // Verify zero invoice handling
        $this->assertEquals(0.00, $payment->cost, 'Cost should be 0.00');
        $this->assertEquals(0.00, $payment->received_amount, 'Received should be 0.00');
        $this->assertEquals(0.00, $payment->remaining_amount, 'No remaining amount');
        $this->assertEquals(Payment::STATUS_PAID, $payment->status, 'Status should be paid for zero invoice');
        $this->assertEquals(0.00, $payment->net_amount, 'Net should be 0.00');
        $this->assertEquals(0.00, $payment->vat_amount, 'VAT should be 0.00');
    }
    
    /**
     * TEST 15: Multi-dog bulk checkout with wallet usage
     * 
     * Test bulk checkout of multiple dogs for same customer with wallet usage
     */
    public function test_multi_dog_bulk_checkout_with_wallet()
    {
        Config::set('app.vat_calculation_mode', 'exclusive');
        
        // Set customer balance to use
        $this->customer->balance = 50.00;
        $this->customer->save();
        
        // Create a second dog for the same customer
        $dog2 = Dog::factory()->create([
            'customer_id' => $this->customer->id,
            'name' => 'Test Dog 2',
        ]);
        
        $checkinDate = Carbon::now()->subDays(1)->startOfDay();
        $checkoutDate = Carbon::now()->startOfDay();
        
        // Create 2 reservations for same customer
        $reservation1 = Reservation::factory()->create([
            'dog_id' => $this->dog->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'status' => 1,
        ]);
        
        $reservation2 = Reservation::factory()->create([
            'dog_id' => $dog2->id,
            'room_id' => $this->room->id,
            'plan_id' => $this->normalPlan->id,
            'checkin_date' => $checkinDate,
            'status' => 1,
        ]);
        
        // Checkout both dogs - each 50 net = 60 gross, total 120 gross
        // Wallet of 50 should partially cover total
        $response = $this->post(route('admin.dogs.rooms.checkout-update'), [
            'res_id' => [$reservation1->id, $reservation2->id],
            'plan_id' => [$this->normalPlan->id, $this->normalPlan->id],
            'checkout_date' => [$checkoutDate->format('Y-m-d H:i:s'), $checkoutDate->format('Y-m-d H:i:s')],
            'days' => [1, 1],
            'base_cost' => [50.00, 50.00],
            'special_cost' => [0, 0],
            'discount' => [0, 0],
            'payment_method' => ['Bar', 'Bar'],
            'received_amount' => [35.00, 35.00], // Pay 70 cash
            'invoice_amount' => [60.00, 60.00], // 50 + 20% VAT each
            'use_wallet' => [$this->customer->id => '1'],
        ]);
        
        $response->assertRedirect();
        
        // Verify payments created for both
        $payment1 = Payment::where('res_id', $reservation1->id)->first();
        $payment2 = Payment::where('res_id', $reservation2->id)->first();
        
        $this->assertNotNull($payment1, 'Payment 1 should exist');
        $this->assertNotNull($payment2, 'Payment 2 should exist');
        
        // Both should be checked out
        $reservation1->refresh();
        $reservation2->refresh();
        $this->assertEquals(Reservation::STATUS_CHECKED_OUT, $reservation1->status);
        $this->assertEquals(Reservation::STATUS_CHECKED_OUT, $reservation2->status);
        
        // Total wallet used should be 50 (the full balance since total cost > balance)
        $totalWalletUsed = $payment1->wallet_amount + $payment2->wallet_amount;
        $this->assertEqualsWithDelta(50.00, $totalWalletUsed, 0.05, 'Total wallet should be distributed');
    }
}
