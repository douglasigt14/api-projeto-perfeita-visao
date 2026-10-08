<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Lead;
use App\Models\Prospector;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prospector_id' => Prospector::factory(),
            'city_id' => City::factory(),
            'name' => fake()->name(),
            'phone_number' => '88'.fake()->unique()->numerify('9########'),
        ];
    }
}
