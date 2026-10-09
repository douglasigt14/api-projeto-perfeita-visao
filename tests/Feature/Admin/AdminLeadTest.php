<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\CityVisit;
use App\Models\Lead;
use App\Models\Prospector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminLeadTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = User::factory()->team(UserRole::FieldAgent)->create(['name' => 'Ana Atendente']);
        Passport::actingAs($this->agent);
    }

    private function leadInVisit(string $start = '2026-11-22', ?string $end = '2026-11-24'): Lead
    {
        $visit = CityVisit::factory()->create(['visit_date' => $start, 'end_date' => $end]);

        return Lead::factory()->create(['city_id' => $visit->city_id, 'city_visit_id' => $visit->id]);
    }

    public function test_lists_leads_with_filters_and_search(): void
    {
        $city = City::factory()->create();
        $prospector = Prospector::factory()->create();
        $maria = Lead::factory()->for($prospector)->create(['city_id' => $city->id, 'name' => 'Maria Souza', 'phone_number' => '88999991234']);
        Lead::factory()->create(['name' => 'João Lima']);
        Lead::factory()->create(['name' => 'Apagada'])->delete();

        $this->getJson('/api/admin/leads')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['id', 'name', 'phone_number', 'status', 'appointment_date', 'city', 'visit', 'prospector' => ['id', 'name'], 'contacts_count']]]);

        foreach (["city_id={$city->id}", "prospector_id={$prospector->id}", 'search=souza', 'search=(88) 99999-1234', 'status=new&search=maria'] as $query) {
            $this->getJson("/api/admin/leads?{$query}")
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $maria->id);
        }

        $this->getJson('/api/admin/leads?status=scheduled')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_schedules_exam_within_the_visit_period(): void
    {
        $lead = $this->leadInVisit();

        foreach (['2026-11-21', '2026-11-25'] as $outside) {
            $this->patchJson("/api/admin/leads/{$lead->id}/schedule", ['appointment_date' => $outside])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['appointment_date' => 'Escolha um dia dentro do período do atendimento.']);
        }

        $this->patchJson("/api/admin/leads/{$lead->id}/schedule", ['appointment_date' => '2026-11-23'])
            ->assertOk()
            ->assertJsonPath('data.appointment_date', '2026-11-23')
            ->assertJsonPath('data.status', 'scheduled');

        // desmarcar volta para "Em contato"
        $this->patchJson("/api/admin/leads/{$lead->id}/schedule", ['appointment_date' => null])
            ->assertOk()
            ->assertJsonPath('data.appointment_date', null)
            ->assertJsonPath('data.status', 'contacting');
    }

    public function test_one_day_visit_only_accepts_that_day(): void
    {
        $lead = $this->leadInVisit('2026-11-22', null);

        $this->patchJson("/api/admin/leads/{$lead->id}/schedule", ['appointment_date' => '2026-11-23'])->assertUnprocessable();
        $this->patchJson("/api/admin/leads/{$lead->id}/schedule", ['appointment_date' => '2026-11-22'])->assertOk();
    }

    public function test_lead_without_visit_cannot_be_scheduled(): void
    {
        $lead = Lead::factory()->create(['city_visit_id' => null]);

        $this->patchJson("/api/admin/leads/{$lead->id}/schedule", ['appointment_date' => '2026-11-22'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['appointment_date']);
    }

    public function test_status_rules(): void
    {
        $lead = $this->leadInVisit();

        $this->patchJson("/api/admin/leads/{$lead->id}/status", ['status' => 'scheduled'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Para agendar, escolha o dia do exame.']);

        $this->patchJson("/api/admin/leads/{$lead->id}/status", ['status' => 'attended'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Marque o dia do exame antes.']);

        $lead->update(['status' => LeadStatus::Scheduled, 'appointment_date' => '2026-11-23']);

        $this->patchJson("/api/admin/leads/{$lead->id}/status", ['status' => 'attended'])
            ->assertOk()
            ->assertJsonPath('data.status', 'attended')
            ->assertJsonPath('data.appointment_date', '2026-11-23');

        // descartar desmarca o exame
        $this->patchJson("/api/admin/leads/{$lead->id}/status", ['status' => 'discarded'])
            ->assertOk()
            ->assertJsonPath('data.status', 'discarded')
            ->assertJsonPath('data.appointment_date', null);
    }

    public function test_registers_contacts_in_the_history(): void
    {
        $lead = $this->leadInVisit();

        $this->postJson("/api/admin/leads/{$lead->id}/contacts", ['channel' => 'whatsapp', 'result' => 'no_answer'])
            ->assertCreated()
            ->assertJsonPath('data.user.name', 'Ana Atendente');
        $this->postJson("/api/admin/leads/{$lead->id}/contacts", ['channel' => 'phone_call', 'result' => 'reached', 'notes' => 'Prefere sábado'])
            ->assertCreated();

        $this->assertSame(LeadStatus::Contacting, $lead->refresh()->status);

        $this->getJson("/api/admin/leads/{$lead->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.contacts')
            ->assertJsonPath('data.contacts.0.notes', 'Prefere sábado')
            ->assertJsonPath('data.contacts.0.channel', 'phone_call');

        $this->postJson("/api/admin/leads/{$lead->id}/contacts", ['channel' => 'pombo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['channel', 'result']);
    }

    public function test_partner_can_only_delete_new_leads(): void
    {
        $partner = User::factory()->create();
        $new = Lead::factory()->for($partner->prospector)->create();
        $scheduled = Lead::factory()->for($partner->prospector)->create(['status' => LeadStatus::Scheduled, 'appointment_date' => now()]);
        Passport::actingAs($partner);

        $this->getJson('/api/leads')->assertOk()->assertJsonPath('data.0.status', 'scheduled');

        $this->deleteJson("/api/leads/{$scheduled->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lead']);
        $this->assertNotSoftDeleted($scheduled);

        $this->deleteJson("/api/leads/{$new->id}")->assertNoContent();
    }
}
