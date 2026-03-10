<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Dog;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerPortalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_and_links_customer_account(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/customer/auth/register', [
            'name' => 'Max Muster',
            'email' => 'max@example.com',
            'password' => 'GeheimesPasswort123',
            'password_confirmation' => 'GeheimesPasswort123',
            'privacy_accepted' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Registrierung gestartet. Bitte E-Mail-Code bestätigen.',
            ]);

        $this->assertDatabaseHas('customer_accounts', [
            'email' => 'max@example.com',
            'status' => 'pending',
        ]);

        $account = CustomerAccount::where('email', 'max@example.com')->firstOrFail();
        $this->assertNotNull($account->customer_id);
        $this->assertDatabaseHas('customer_verification_codes', [
            'customer_account_id' => $account->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $account->customer_id,
            'email' => 'max@example.com',
            'type' => 'Stammkunde',
            'picture' => 'no-user-picture.gif',
        ]);
    }

    public function test_can_create_multiple_dogs_in_single_request(): void
    {
        $customer = Customer::factory()->create(['email' => 'kunde@example.com', 'type' => 'Stammkunde']);
        $account = CustomerAccount::create([
            'customer_id' => $customer->id,
            'email' => 'kunde@example.com',
            'password' => 'GeheimesPasswort123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Plan::factory()->count(2)->create();

        Sanctum::actingAs($account);

        $response = $this->postJson('/api/customer/dogs', [
            'dogs' => [
                ['name' => 'Bello'],
                ['name' => 'Luna', 'race' => 'Mischling'],
            ],
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => ['count' => 2],
            ]);

        $this->assertDatabaseHas('dogs', [
            'customer_id' => (string) $customer->id,
            'name' => 'Bello',
        ]);
        $this->assertDatabaseHas('dogs', [
            'customer_id' => (string) $customer->id,
            'name' => 'Luna',
            'compatible_breed' => 'Mischling',
        ]);
    }

    public function test_get_dogs_returns_only_own_records(): void
    {
        $customer = Customer::factory()->create(['type' => 'Stammkunde']);
        $otherCustomer = Customer::factory()->create(['type' => 'Stammkunde']);

        $account = CustomerAccount::create([
            'customer_id' => $customer->id,
            'email' => 'kunde2@example.com',
            'password' => 'GeheimesPasswort123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Dog::factory()->create([
            'customer_id' => (string) $customer->id,
            'name' => 'Eigener Hund',
        ]);
        Dog::factory()->create([
            'customer_id' => (string) $otherCustomer->id,
            'name' => 'Fremder Hund',
        ]);

        Sanctum::actingAs($account);
        $response = $this->getJson('/api/customer/dogs');

        $response->assertOk()->assertJson(['success' => true]);
        $dogs = $response->json('data.dogs');
        $this->assertCount(1, $dogs);
        $this->assertSame('Eigener Hund', $dogs[0]['name']);
    }

    public function test_reservation_uses_default_plan_based_on_stay_days(): void
    {
        $customer = Customer::factory()->create(['type' => 'Stammkunde']);
        $account = CustomerAccount::create([
            'customer_id' => $customer->id,
            'email' => 'kunde3@example.com',
            'password' => 'GeheimesPasswort123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $plan1 = Plan::factory()->create();
        $plan2 = Plan::factory()->create();
        $dog = Dog::factory()->create([
            'customer_id' => (string) $customer->id,
            'name' => 'PlanTest',
        ]);

        Sanctum::actingAs($account);

        $sameDayResponse = $this->postJson('/api/customer/reservations', [
            'dog_id' => $dog->id,
            'checkin_date' => now()->addDays(1)->toDateString(),
            'checkout_date' => now()->addDays(1)->toDateString(),
        ]);
        $sameDayResponse->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('reservations', [
            'dog_id' => $dog->id,
            'plan_id' => $plan1->id,
        ]);

        $multiDayResponse = $this->postJson('/api/customer/reservations', [
            'dog_id' => $dog->id,
            'checkin_date' => now()->addDays(5)->toDateString(),
            'checkout_date' => now()->addDays(7)->toDateString(),
        ]);
        $multiDayResponse->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('reservations', [
            'dog_id' => $dog->id,
            'checkin_date' => now()->addDays(5)->startOfDay()->addMinutes(5)->toDateTimeString(),
            'plan_id' => $plan2->id,
        ]);
    }

    public function test_customer_cannot_create_reservation_for_other_customer_dog(): void
    {
        $customer = Customer::factory()->create(['type' => 'Stammkunde']);
        $otherCustomer = Customer::factory()->create(['type' => 'Stammkunde']);

        $account = CustomerAccount::create([
            'customer_id' => $customer->id,
            'email' => 'kunde2@example.com',
            'password' => 'GeheimesPasswort123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Plan::factory()->count(2)->create();
        $foreignDog = Dog::factory()->create([
            'customer_id' => (string) $otherCustomer->id,
        ]);

        Sanctum::actingAs($account);

        $response = $this->postJson('/api/customer/reservations', [
            'dog_id' => $foreignDog->id,
            'checkin_date' => now()->addDays(1)->toDateString(),
            'checkout_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertStatus(403)->assertJson(['success' => false]);
    }
}
