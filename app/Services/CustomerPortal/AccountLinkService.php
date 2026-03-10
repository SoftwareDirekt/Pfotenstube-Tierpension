<?php

namespace App\Services\CustomerPortal;

use App\Models\Customer;
use App\Models\CustomerAccount;

class AccountLinkService
{
    public function linkOrCreateCustomerForAccount(CustomerAccount $account, array $payload): Customer
    {
        $customer = Customer::where('email', $account->email)->first();
        $type = $payload['type'] ?? 'Stammkunde';

        if (!$customer) {
            $customer = Customer::create([
                'type' => $type,
                'name' => $payload['name'],
                'email' => $account->email,
                'phone' => $payload['phone'] ?? null,
                'street' => $payload['street'] ?? null,
                'city' => $payload['city'] ?? null,
                'zipcode' => $payload['zipcode'] ?? null,
                'country' => $payload['country'] ?? null,
                'picture' => 'no-user-picture.gif',
            ]);
        } else {
            $customer->fill([
                'type' => $customer->type ?: $type,
                'name' => $customer->name ?: ($payload['name'] ?? $customer->name),
                'phone' => $payload['phone'] ?? $customer->phone,
                'street' => $payload['street'] ?? $customer->street,
                'city' => $payload['city'] ?? $customer->city,
                'zipcode' => $payload['zipcode'] ?? $customer->zipcode,
                'country' => $payload['country'] ?? $customer->country,
                'picture' => $customer->picture ?: 'no-user-picture.gif',
            ]);
            $customer->save();
        }

        if (!$account->customer_id || $account->customer_id !== $customer->id) {
            $account->customer_id = $customer->id;
            $account->save();
        }

        return $customer;
    }
}
