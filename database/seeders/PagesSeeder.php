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
        Page::insert([
            [
                'name' => 'Mitarbeiter'
            ],
            [
                'name' => 'Armaturenbrett'
            ],
            [
                'name' => 'Zimmer'
            ],
            [
                'name' => 'Kunde'
            ],
            [
                'name' => 'Reservierung'
            ],
            [
                'name' => 'Zahlung'
            ],
            [
                'name' => 'Verkaufsbericht'
            ],
            [
                'name' => 'Aufgaben hinzufugen'
            ],
            [
                'name' => 'Preisplane'
            ],
            [
                'name' => 'Hunde bleiben'
            ],
            [
                'name' => 'Kalender'
            ],
            [
                'name' => 'Hundekalender'
            ],
            [
                'name' => 'Verstorbene Hunde'
            ],
            [
                'name' => 'Kunden ansehen'
            ],
            [
                'name' => 'Hund hinzufugen'
            ]
        ]);
    }
}
