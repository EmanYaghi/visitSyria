<?php

namespace Database\Factories;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

class TripFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => $this->faker->randomElement([1, 2]),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'season' => $this->faker->randomElement(['الصيف', 'الخريف', 'الشتاء', 'الربيع']),
            'start_date' => $this->faker->dateTimeBetween('2024-01-01', '2026-12-31')->format('Y-m-d'),
            'days'=>$this->faker->numberBetween(0, 10),
            'hours'=>$this->faker->numberBetween(0, 10),
            'duration' => '3days 9hours',
            'improvements' => json_encode($this->faker->words(3)),
            'tickets' => $this->faker->numberBetween(10, 100),
            'reserved_tickets' => $this->faker->numberBetween(0, 10),
            'price' => $this->faker->randomFloat(2, 100, 1000),
            'discount' => $this->faker->randomFloat(2, 0, 50),
            'new_price' => function (array $attributes) {
                return $attributes['price'] - ($attributes['price'] * $attributes['discount'] / 100);
            },
            'status' =>  function (array $attributes) {
                $startDate = $attributes['start_date'];
                $currentDate = now();
                if ($startDate > $currentDate) {
                    return 'لم تبدأ بعد';
                } else if ($startDate==$currentDate) {
                    return 'جارية';
                } else {
                    return 'منتهية';
                }
            },

        ];
    }
}
