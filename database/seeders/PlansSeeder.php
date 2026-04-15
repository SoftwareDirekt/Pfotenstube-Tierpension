<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'title' => 'Tag &euro; 25',
                'type' => 'Kleiner Hund ',
                'price' => '25.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'Tag &euro; 28',
                'type' => 'GroÃŸer Hund',
                'price' => '28.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'Pension &euro; 28',
                'type' => 'Tarif K H',
                'price' => '28.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'Pension &euro; 30',
                'type' => 'Tarif G H',
                'price' => '30.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],

            [
                'title' => '2 Hunde Tag &euro; 45',
                'type' => '2 Hunde',
                'price' => '45.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => '2 Hunde Pension &euro; 50',
                'type' => '2 Hunde',
                'price' => '50.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'TSV Tarif &euro; 25',
                'type' => 'TSV Tarif 25',
                'price' => '25.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'TSV Tarif &euro; 20',
                'type' => 'TSV Tarif 20',
                'price' => '20.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'TSV Tarif &euro; 15',
                'type' => 'TSV Tarif 15',
                'price' => '15.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => '3 Hunde Tag &euro; 66',
                'type' => '3 Hunde Tag',
                'price' => '66.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],

            [
                'title' => '3 Hunde Pension &euro; 66',
                'type' => '3 Hunde P',
                'price' => '66.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'Tag &euro; 20',
                'type' => 'Tages &euro; 20',
                'price' => '20.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => '3 Std. &euro; 10',
                'type' => '3 Std. &euro; 10',
                'price' => '10.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],

            [
                'title' => 'Tag &euro; 22,50',
                'type' => 'Tag &euro; 22,50',
                'price' => '22.50',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
            [
                'title' => 'Freunde',
                'type' => '0',
                'price' => '0.00',
                'discount' => 0.00,
                'flat_rate' => 0,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                [
                    'title' => $plan['title'],
                    'type' => $plan['type'],
                ],
                [
                    'price' => $plan['price'],
                    'discount' => $plan['discount'],
                    'flat_rate' => $plan['flat_rate'],
                ]
            );
        }
    }
}
