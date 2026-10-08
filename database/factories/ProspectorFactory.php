<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Prospector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospector>
 */
class ProspectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => fake()->name(),
            'birth_date' => fake()->date(max: '-18 years'),
            'pix_key' => fake()->safeEmail(),
            'phone_number' => '889'.fake()->unique()->numerify('########'),
            'instagram_handle' => fake()->userName(),
        ];
    }
}
