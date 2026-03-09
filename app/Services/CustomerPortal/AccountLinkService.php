<?php

namespace App\Services\CustomerPortal;

use App\Models\Customer;
use App\Models\CustomerAccount;

class AccountLinkService
{
    public function linkOrCreateCustomerForAccount(CustomerAccount $account, array $payload): Customer
    {
        $customer = Customer::where('email', $account->email)->first();

        if (!$customer) {
            $customer = Customer::create([
                'type' => $payload['type'] ?? 'Stammkunde',
                'name' => $payload['name'],
                'email' => $account->email,
                'phone' => $payload['phone'] ?? null,
                'street' => $payload['street'] ?? null,
                'city' => $payload['city'] ?? null,
                'zipcode' => $payload['zipcode'] ?? null,
                'country' => $payload['country'] ?? null,
                'picture' => 'no-user-picture.gif',
            ]);
        }

        if (!$account->customer_id || $account->customer_id !== $customer->id) {
            $account->customer_id = $customer->id;
            $account->save();
        }

        return $customer;
    }
}
