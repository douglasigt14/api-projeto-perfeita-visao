<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Testes',
            '--provider' => 'users',
            '--no-interaction' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Souza',
            'birth_date' => '1995-04-12',
            'phone_number' => '(88) 99999-1234',
            'city_id' => City::factory()->create()->id,
            'pix_key' => 'maria@exemplo.com',
            'instagram_handle' => 'maria.souza',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ], $overrides);
    }

    public function test_lists_cities_for_the_registration_form(): void
    {
        City::factory()->create(['name' => 'Trairi']);
        City::factory()->create(['name' => 'Amontada']);

        $this->getJson('/api/cities')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Amontada')
            ->assertJsonPath('data.1.name', 'Trairi');
    }

    public function test_registration_creates_prospector_and_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', $this->registrationData());

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'prospector' => ['city']]])
            ->assertJsonPath('user.phone_number', '88999991234')
            ->assertJsonPath('user.prospector.name', 'Maria Souza');

        $this->assertDatabaseHas('prospectors', ['phone_number' => '88999991234', 'pix_key' => 'maria@exemplo.com']);
        $user = User::firstWhere('phone_number', '88999991234');
        $this->assertNotNull($user->prospector);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.prospector.name', 'Maria Souza');
    }

    public function test_registration_rejects_duplicate_phone_and_minors(): void
    {
        User::factory()->create(['phone_number' => '88999991234']);

        $this->postJson('/api/register', $this->registrationData([
            'birth_date' => now()->subYears(16)->toDateString(),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number', 'birth_date']);

        $this->assertDatabaseCount('prospectors', 1);
    }

    public function test_login_with_phone_and_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['phone_number' => $user->phone_number, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['access_token'])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['phone_number' => $user->phone_number, 'password' => 'errada'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number']);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('app');

        $this->withToken($token->accessToken)->postJson('/api/logout')->assertNoContent();

        $this->assertTrue($token->token->fresh()->revoked);
    }

    public function test_me_with_passport_acting_as(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->getJson('/api/me')->assertOk()->assertJsonStructure(['data' => ['prospector' => ['city' => ['name', 'state']]]]);
    }
}
