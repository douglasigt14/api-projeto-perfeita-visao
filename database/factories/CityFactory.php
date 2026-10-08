<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ibge_code' => fake()->unique()->numberBetween(1100000, 5399999),
            'name' => fake()->unique()->city(),
            'state' => 'CE',
            'active' => true,
        ];
    }

    /**
     * Cidade desativada (não aparece no cadastro).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
