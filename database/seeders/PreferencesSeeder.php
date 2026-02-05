<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Preference;

class PreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds default system preferences.
     */
    public function run(): void
    {
        $preferences = [
            [
                'key' => 'vat_percentage',
                'value' => '20',
                'type' => 'float',
                'description' => 'MwSt-Prozentsatz für Rechnungsberechnungen',
            ],
        ];

        foreach ($preferences as $preference) {
            // Only insert if key doesn't exist (upsert without overwrite)
            Preference::firstOrCreate(
                ['key' => $preference['key']],
                [
                    'value' => $preference['value'],
                    'type' => $preference['type'],
                    'description' => $preference['description'],
                ]
            );
        }
    }
}
