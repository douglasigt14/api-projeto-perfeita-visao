<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_prospector_creates_a_lead(): void
    {
        $user = User::factory()->create();
        $city = City::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/leads', [
            'name' => 'João Pereira',
            'phone_number' => '(88) 98888-7777',
            'city_id' => $city->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'João Pereira')
            ->assertJsonPath('data.phone_number', '88988887777')
            ->assertJsonPath('data.city.id', $city->id);

        $this->assertDatabaseHas('leads', [
            'prospector_id' => $user->prospector_id,
            'city_id' => $city->id,
            'phone_number' => '88988887777',
        ]);
    }

    public function test_prospector_lists_only_own_leads_newest_first(): void
    {
        $user = User::factory()->create();
        $older = Lead::factory()->for($user->prospector)->create(['created_at' => now()->subDay()]);
        $newer = Lead::factory()->for($user->prospector)->create();
        Lead::factory()->create(); // de outro prospector
        Passport::actingAs($user);

        $this->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonStructure(['data' => [['id', 'name', 'phone_number', 'city' => ['id', 'name', 'state'], 'created_at']]]);
    }

    public function test_lead_requires_name_phone_and_active_city(): void
    {
        Passport::actingAs(User::factory()->create());
        $inactiveCity = City::factory()->inactive()->create();

        $this->postJson('/api/leads', ['phone_number' => '123', 'city_id' => $inactiveCity->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone_number', 'city_id']);
    }

    public function test_lead_requires_authentication(): void
    {
        $this->postJson('/api/leads', [])->assertUnauthorized();
        $this->getJson('/api/leads')->assertUnauthorized();
    }

    public function test_user_without_prospector_cannot_create_lead(): void
    {
        Passport::actingAs(User::factory()->create(['prospector_id' => null, 'phone_number' => '88911112222']));

        $this->postJson('/api/leads', [
            'name' => 'João Pereira',
            'phone_number' => '88988887777',
            'city_id' => City::factory()->create()->id,
        ])->assertForbidden();
    }

    public function test_client_can_be_linked_to_the_lead_it_came_from(): void
    {
        $lead = Lead::factory()->create();

        $client = Client::create([
            'lead_id' => $lead->id,
            'city_id' => $lead->city_id,
            'name' => $lead->name,
            'phone_number' => $lead->phone_number,
        ]);

        $this->assertTrue($client->lead->is($lead));
        $this->assertTrue($lead->client->is($client));
    }
}
