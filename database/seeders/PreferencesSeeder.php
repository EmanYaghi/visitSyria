<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PreferencesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now()->toDateTimeString();

        DB::table('preferences')->insert([
            [
                'user_id' => 3,
                'preferred_season' => json_encode(['spring', 'autumn']),
                'preferred_activities' => json_encode(['sightseeing', 'museums', 'food']),
                'duration' => json_encode(['min_days' => 1, 'max_days' => 5]),
                'cities' => json_encode(['Damascus', 'Aleppo', 'Tartus']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 4,
                'preferred_season' => json_encode(['summer']),
                'preferred_activities' => json_encode(['beach', 'relaxation', 'water_sports']),
                'duration' => json_encode(['min_days' => 3, 'max_days' => 14]),
                'cities' => json_encode(['Latakia', 'Baniyas']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
