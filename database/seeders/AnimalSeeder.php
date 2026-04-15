<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dog;
use Illuminate\Database\Seeder;

class AnimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $max = Customer::where('email', 'max.mustermann@example.com')->first();
        $anna = Customer::where('email', 'anna.berger@example.com')->first();

        if (! $max || ! $anna) {
            return;
        }

        $animals = [
            [
                'customer_id' => $max->id,
                'name' => 'Balu',
                'age' => '4',
                'gender' => 'male',
                'weight' => '28',
                'chip_number' => 'AT-CHIP-0001',
                'neutered' => 1,
                'water_lover' => 1,
                'status' => 1,
            ],
            [
                'customer_id' => $anna->id,
                'name' => 'Luna',
                'age' => '2',
                'gender' => 'female',
                'weight' => '18',
                'chip_number' => 'AT-CHIP-0002',
                'neutered' => 0,
                'water_lover' => 0,
                'status' => 1,
            ],
        ];

        foreach ($animals as $animal) {
            Dog::updateOrCreate(
                [
                    'customer_id' => $animal['customer_id'],
                    'name' => $animal['name'],
                ],
                $animal
            );
        }
    }
}
