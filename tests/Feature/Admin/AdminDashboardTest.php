<?php

namespace Tests\Feature\Admin;

use App\Enums\CityVisitStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\CityVisit;
use App\Models\Lead;
use App\Models\Prospector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 20/10/2026 13:00 em Fortaleza
        $this->travelTo(Carbon::parse('2026-10-20 16:00:00', 'UTC'));
        Passport::actingAs(User::factory()->team()->create());
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_summarizes_the_current_month_by_default(): void
    {
        $iguatu = City::factory()->create(['name' => 'Iguatu']);
        $aracati = City::factory()->create(['name' => 'Aracati']);
        $maria = Prospector::factory()->create(['name' => 'Maria', 'city_id' => $iguatu->id]);
        $joao = Prospector::factory()->create(['name' => 'João', 'city_id' => $aracati->id, 'created_at' => '2026-09-10']);
        $visit = CityVisit::factory()->create(['city_id' => $iguatu->id, 'visit_date' => '2026-10-15', 'end_date' => '2026-10-17']);

        // João indicou mais, mas só a indicação da Maria compareceu: Maria fica em primeiro.
        Lead::factory()->count(3)->create(['prospector_id' => $joao->id, 'city_id' => $aracati->id]);
        Lead::factory()->create(['prospector_id' => $maria->id, 'city_id' => $iguatu->id, 'city_visit_id' => $visit->id, 'status' => LeadStatus::Attended, 'appointment_date' => '2026-10-16']);
        Lead::factory()->create(['prospector_id' => $maria->id, 'city_id' => $iguatu->id, 'city_visit_id' => $visit->id, 'status' => LeadStatus::Scheduled, 'appointment_date' => '2026-10-17']);
        // fora do período e apagada não contam
        Lead::factory()->create(['prospector_id' => $maria->id, 'city_id' => $iguatu->id, 'created_at' => '2026-09-30 12:00:00']);
        Lead::factory()->create(['prospector_id' => $maria->id, 'city_id' => $iguatu->id])->delete();

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.period', ['from' => '2026-10-01', 'to' => '2026-10-31'])
            ->assertJsonPath('data.leads.total', 5)
            ->assertJsonPath('data.leads.with_appointment', 2)
            ->assertJsonPath('data.leads.attended', 1)
            ->assertJsonPath('data.leads.by_status.new', 3)
            ->assertJsonPath('data.leads.by_status.discarded', 0)
            ->assertJsonPath('data.leads_by_city.0.city.name', 'Aracati')
            ->assertJsonPath('data.leads_by_city.0.total', 3)
            ->assertJsonPath('data.leads_by_city.1.attended', 1)
            ->assertJsonPath('data.prospectors.active', 2)
            ->assertJsonPath('data.prospectors.new', 1)
            ->assertJsonPath('data.ranking.0.prospector.name', 'Maria')
            ->assertJsonPath('data.ranking.0.leads_count', 2)
            ->assertJsonPath('data.ranking.0.with_appointment_count', 2)
            ->assertJsonPath('data.ranking.0.attended_count', 1)
            ->assertJsonPath('data.ranking.1.prospector.name', 'João')
            ->assertJsonPath('data.ranking.1.leads_count', 3);
    }

    public function test_filters_by_city_and_period(): void
    {
        $iguatu = City::factory()->create();
        CityVisit::factory()->create(['city_id' => $iguatu->id, 'visit_date' => '2026-11-22']);
        CityVisit::factory()->create(['city_id' => $iguatu->id, 'visit_date' => '2026-10-15', 'status' => CityVisitStatus::Completed]);
        CityVisit::factory()->create(['city_id' => $iguatu->id, 'visit_date' => '2026-11-05', 'status' => CityVisitStatus::Cancelled]);
        CityVisit::factory()->create(['visit_date' => '2026-11-10']); // outra cidade
        Lead::factory()->create(['city_id' => $iguatu->id, 'created_at' => '2026-11-03 12:00:00']);
        Lead::factory()->create(['created_at' => '2026-11-03 12:00:00']); // outra cidade

        $this->getJson("/api/admin/dashboard?city_id={$iguatu->id}&from=2026-11-01&to=2026-11-30")
            ->assertOk()
            ->assertJsonPath('data.leads.total', 1)
            ->assertJsonPath('data.visits.total', 2)
            ->assertJsonPath('data.visits.by_status.scheduled', 1)
            ->assertJsonPath('data.visits.by_status.cancelled', 1)
            ->assertJsonPath('data.visits.by_status.completed', 0);
    }

    public function test_period_days_follow_the_business_timezone(): void
    {
        // 01/11 01:00 UTC ainda é 31/10 em Fortaleza
        Lead::factory()->create(['created_at' => '2026-11-01 01:00:00']);

        $this->getJson('/api/admin/dashboard?from=2026-10-31&to=2026-10-31')->assertJsonPath('data.leads.total', 1);
        $this->getJson('/api/admin/dashboard?from=2026-11-01&to=2026-11-01')->assertJsonPath('data.leads.total', 0);
    }

    public function test_validates_the_period(): void
    {
        $this->getJson('/api/admin/dashboard?from=2026-11-10&to=2026-11-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to' => 'A data final não pode ser antes da inicial.']);
    }

    public function test_field_agent_sees_it_and_factory_does_not(): void
    {
        Passport::actingAs(User::factory()->team(UserRole::FieldAgent)->create());
        $this->getJson('/api/admin/dashboard')->assertOk();

        Passport::actingAs(User::factory()->team(UserRole::Factory)->create());
        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }
}
