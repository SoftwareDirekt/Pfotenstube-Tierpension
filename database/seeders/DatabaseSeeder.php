<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PagesSeeder::class,
            // PlansSeeder::class,
            PreferencesSeeder::class,
            // CustomerSeeder::class,
            // RoomSeeder::class,
            // AnimalSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => 1,
                'status' => 1,
                'type' => 4,
            ]
        );
    }
}
