<?php

namespace Database\Factories;

use App\Enums\CityVisitStatus;
use App\Models\City;
use App\Models\CityVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CityVisit>
 */
class CityVisitFactory extends Factory
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
            'title' => 'Atendimento '.fake()->monthName(),
            'visit_date' => now()->addDays(10)->toDateString(),
            'end_date' => null,
            'status' => CityVisitStatus::Scheduled,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
