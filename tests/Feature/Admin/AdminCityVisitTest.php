<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Models\City;
use App\Models\CityVisit;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminCityVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Passport::actingAs(User::factory()->team()->create());
    }

    public function test_admin_creates_a_visit(): void
    {
        $city = City::factory()->create();

        $this->postJson('/api/admin/city-visits', [
            'city_id' => $city->id,
            'title' => 'Atendimento Novembro',
            'visit_date' => '2026-11-22',
            'end_date' => '2026-11-23',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Atendimento Novembro')
            ->assertJsonPath('data.city.id', $city->id)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.active', false)
            ->assertJsonPath('data.leads_count', 0);
    }

    public function test_visit_validation(): void
    {
        $inactive = City::factory()->inactive()->create();

        $this->postJson('/api/admin/city-visits', [
            'city_id' => $inactive->id,
            'visit_date' => '2026-11-22',
            'end_date' => '2026-11-20',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'city_id' => 'Escolha uma cidade ativa.',
                'title',
                'end_date' => 'A data de fim não pode ser antes da data de início.',
            ]);
    }

    public function test_admin_edits_activates_and_cancels_a_visit(): void
    {
        $visit = CityVisit::factory()->inactive()->create(['visit_date' => '2026-11-22']);

        $this->patchJson("/api/admin/city-visits/{$visit->id}", ['title' => 'Mutirão', 'active' => true])
            ->assertOk()
            ->assertJsonPath('data.title', 'Mutirão')
            ->assertJsonPath('data.active', true);

        $this->patchJson("/api/admin/city-visits/{$visit->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        // fim antes do início já salvo
        $this->patchJson("/api/admin/city-visits/{$visit->id}", ['end_date' => '2026-11-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_lists_visits_with_filters_and_counts(): void
    {
        $city = City::factory()->create();
        $november = CityVisit::factory()->create(['city_id' => $city->id, 'visit_date' => '2026-11-22', 'end_date' => '2026-11-24']);
        $october = CityVisit::factory()->create(['city_id' => $city->id, 'visit_date' => '2026-10-15']);
        CityVisit::factory()->create(['visit_date' => '2026-12-01']); // outra cidade
        Lead::factory()->create(['city_id' => $city->id, 'city_visit_id' => $november->id]);
        Lead::factory()->create(['city_id' => $city->id, 'city_visit_id' => $november->id, 'status' => LeadStatus::Scheduled, 'appointment_date' => '2026-11-23']);

        $this->getJson("/api/admin/city-visits?city_id={$city->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $november->id)
            ->assertJsonPath('data.0.leads_count', 2)
            ->assertJsonPath('data.0.scheduled_count', 1)
            ->assertJsonPath('data.1.id', $october->id);

        // período do calendário: pega o de novembro, que vai até o dia 24
        $this->getJson('/api/admin/city-visits?from=2026-11-24&to=2026-11-30')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $november->id);
    }
}
