<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'type' => 'private',
                'title' => 'Mr.',
                'profession' => null,
                'name' => 'Max Mustermann',
                'email' => 'max.mustermann@example.com',
                'street' => 'Hauptstrasse 1',
                'city' => 'Wien',
                'zipcode' => '1010',
                'country' => 'Austria',
                'phone' => '+43 660 1111111',
                'emergency_contact' => '+43 660 2222222',
                'veterinarian' => 'Tierarzt Dr. Weber',
                'id_number' => 'CUST-1001',
            ],
            [
                'type' => 'private',
                'title' => 'Ms.',
                'profession' => null,
                'name' => 'Anna Berger',
                'email' => 'anna.berger@example.com',
                'street' => 'Ringstrasse 12',
                'city' => 'Graz',
                'zipcode' => '8010',
                'country' => 'Austria',
                'phone' => '+43 660 3333333',
                'emergency_contact' => '+43 660 4444444',
                'veterinarian' => 'Tierarzt Dr. Huber',
                'id_number' => 'CUST-1002',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['email' => $customer['email']],
                $customer
            );
        }
    }
}
