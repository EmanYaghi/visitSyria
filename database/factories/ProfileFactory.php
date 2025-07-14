<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date(),
            'gender' => fake()->randomElement(['male', 'female']),
            'country' => fake()->country(),
            'phone' => fake()->phoneNumber(),
            'country_code' => '+963',
            'lang' => 'ar',
            'theme_mode' => 'light',
            'allow_notification' => true,
            'experience' => fake()->randomElement(['beginner', 'intermediate', 'expert']),
            'number_of_trips' => fake()->numberBetween(0, 100),
            'rating' => fake()->randomFloat(1, 0, 5),
        ];
    }
}