<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionsSeeder::class);
        $this->call(CitiesTableSeeder::class);
        $this->call(MediaTableSeeder::class);

        User::factory()->create([
            'email' => 'test@example.com',
        ]);
    }
}
