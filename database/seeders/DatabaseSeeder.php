<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionsSeeder::class,
            CitiesTableSeeder::class,
            MediaTableSeeder::class,
            TagNameSeeder::class,
            PlaceSeeder::class,
            EventSeeder::class,

        ]);
        User::factory()->create([
            'email' => 'test@example.com',
        ]);
    }
}
