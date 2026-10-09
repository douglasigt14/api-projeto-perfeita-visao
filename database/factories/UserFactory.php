<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Prospector;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prospector_id' => Prospector::factory(),
            'phone_number' => fn (array $attributes) => Prospector::find($attributes['prospector_id'])->phone_number,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Usuário da equipe interna (sem prospector, entra por e-mail).
     */
    public function team(UserRole $role = UserRole::Admin): static
    {
        return $this->state(fn () => [
            'prospector_id' => null,
            'phone_number' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => $role,
        ]);
    }
}
