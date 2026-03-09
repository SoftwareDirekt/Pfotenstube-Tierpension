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

    public function test_register_creates_account_and_verification_code(): void
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
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_accounts', [
            'email' => 'max@example.com',
            'status' => 'pending',
        ]);

        $account = CustomerAccount::where('email', 'max@example.com')->firstOrFail();
        $this->assertNotNull($account->customer_id);
        $this->assertDatabaseHas('customer_verification_codes', [
            'customer_account_id' => $account->id,
        ]);
    }

    public function test_authenticated_customer_can_create_own_reservation(): void
    {
        $customer = Customer::factory()->create(['email' => 'kunde@example.com', 'type' => 'Stammkunde']);
        $account = CustomerAccount::create([
            'customer_id' => $customer->id,
            'email' => 'kunde@example.com',
            'password' => 'GeheimesPasswort123',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $plan = Plan::factory()->create();
        $dog = Dog::factory()->create([
            'customer_id' => (string) $customer->id,
            'reg_plan' => $plan->id,
        ]);

        Sanctum::actingAs($account);

        $response = $this->postJson('/api/customer/reservations', [
            'dog_id' => $dog->id,
            'checkin_date' => now()->addDays(1)->toDateString(),
            'checkout_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reservations', [
            'dog_id' => $dog->id,
            'status' => 3,
            'plan_id' => $plan->id,
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

        $plan = Plan::factory()->create();
        $foreignDog = Dog::factory()->create([
            'customer_id' => (string) $otherCustomer->id,
            'reg_plan' => $plan->id,
        ]);

        Sanctum::actingAs($account);

        $response = $this->postJson('/api/customer/reservations', [
            'dog_id' => $foreignDog->id,
            'checkin_date' => now()->addDays(1)->toDateString(),
            'checkout_date' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertForbidden();
    }
}
