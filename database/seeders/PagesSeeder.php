<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Page;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            'Mitarbeiter',
            'Armaturenbrett',
            'Zimmer',
            'Kunde',
            'Reservierung',
            'Zahlung',
            'Verkaufsbericht',
            'Aufgaben hinzufugen',
            'Preisplane',
            'Hunde bleiben',
            'Kalender',
            'Hundekalender',
            'Verstorbene Hunde',
            'Kunden ansehen',
            'Hund hinzufugen',
            'Rechnungen',
        ];

        foreach ($pages as $pageName) {
            Page::firstOrCreate(
                ['name' => $pageName],
                ['name' => $pageName]
            );
        }
    }
}
