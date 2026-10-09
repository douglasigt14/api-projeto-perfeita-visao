<?php

namespace Tests\Feature;

use App\Enums\CityVisitStatus;
use App\Models\City;
use App\Models\CityVisit;
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
        $visit = CityVisit::factory()->create(['title' => 'Atendimento Outubro']);
        Passport::actingAs($user);

        $this->postJson('/api/leads', [
            'name' => 'João Pereira',
            'phone_number' => '(88) 98888-7777',
            'city_id' => $visit->city_id,
            'city_visit_id' => $visit->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'João Pereira')
            ->assertJsonPath('data.phone_number', '88988887777')
            ->assertJsonPath('data.city.id', $visit->city_id)
            ->assertJsonPath('data.visit.id', $visit->id)
            ->assertJsonPath('data.visit.title', 'Atendimento Outubro');

        $this->assertDatabaseHas('leads', [
            'prospector_id' => $user->prospector_id,
            'city_id' => $visit->city_id,
            'city_visit_id' => $visit->id,
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
            ->assertJsonStructure(['data' => [['id', 'name', 'phone_number', 'city' => ['id', 'name', 'state'], 'visit' => ['id', 'title', 'visit_date'], 'created_at']]]);
    }

    public function test_lead_requires_name_phone_and_active_city(): void
    {
        Passport::actingAs(User::factory()->create());
        $inactiveCity = City::factory()->inactive()->create();

        $this->postJson('/api/leads', ['phone_number' => '123', 'city_id' => $inactiveCity->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone_number', 'city_id', 'city_visit_id']);
    }

    public function test_lead_visit_must_be_open_and_from_the_chosen_city(): void
    {
        Passport::actingAs(User::factory()->create());
        $city = City::factory()->create();
        $closed = [
            CityVisit::factory()->create(), // de outra cidade
            CityVisit::factory()->create(['city_id' => $city->id, 'visit_date' => now()->subDay()->toDateString()]),
            CityVisit::factory()->create(['city_id' => $city->id, 'status' => CityVisitStatus::Cancelled]),
            CityVisit::factory()->create(['city_id' => $city->id, 'status' => CityVisitStatus::Completed]),
            CityVisit::factory()->inactive()->create(['city_id' => $city->id]),
        ];

        foreach ($closed as $visit) {
            $this->postJson('/api/leads', [
                'name' => 'João Pereira',
                'phone_number' => '88988887777',
                'city_id' => $city->id,
                'city_visit_id' => $visit->id,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['city_visit_id' => 'Escolha um atendimento marcado para esta cidade.']);
        }
    }

    public function test_lists_open_visits_of_a_city(): void
    {
        Passport::actingAs(User::factory()->create());
        $city = City::factory()->create();
        $later = CityVisit::factory()->create(['city_id' => $city->id, 'visit_date' => now()->addDays(20)->toDateString()]);
        $sooner = CityVisit::factory()->create(['city_id' => $city->id, 'visit_date' => now()->toDateString()]);
        $ongoing = CityVisit::factory()->create([
            'city_id' => $city->id,
            'visit_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'status' => CityVisitStatus::InProgress,
        ]);
        CityVisit::factory()->create(['city_id' => $city->id, 'visit_date' => now()->subDay()->toDateString()]);
        CityVisit::factory()->create(['city_id' => $city->id, 'status' => CityVisitStatus::Cancelled]);
        CityVisit::factory()->inactive()->create(['city_id' => $city->id]);
        CityVisit::factory()->create(); // de outra cidade

        $this->getJson("/api/cities/{$city->id}/visits")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $ongoing->id)
            ->assertJsonPath('data.1.id', $sooner->id)
            ->assertJsonPath('data.2.id', $later->id)
            ->assertJsonStructure(['data' => [['id', 'title', 'visit_date', 'end_date', 'status']]]);
    }

    public function test_visits_of_inactive_city_are_not_listed(): void
    {
        Passport::actingAs(User::factory()->create());
        $city = City::factory()->inactive()->create();

        $this->getJson("/api/cities/{$city->id}/visits")->assertNotFound();
    }

    public function test_lead_requires_authentication(): void
    {
        $this->postJson('/api/leads', [])->assertUnauthorized();
        $this->getJson('/api/leads')->assertUnauthorized();
        $this->getJson('/api/cities/1/visits')->assertUnauthorized();
    }

    public function test_user_without_prospector_cannot_create_lead(): void
    {
        Passport::actingAs(User::factory()->create(['prospector_id' => null, 'phone_number' => '88911112222']));

        $this->postJson('/api/leads', [
            'name' => 'João Pereira',
            'phone_number' => '88988887777',
            'city_id' => City::factory()->create()->id,
            'city_visit_id' => 1,
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
