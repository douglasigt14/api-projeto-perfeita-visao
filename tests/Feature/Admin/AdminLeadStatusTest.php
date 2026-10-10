<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStage;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminLeadStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Passport::actingAs(User::factory()->team()->create());
    }

    public function test_lists_the_default_statuses_in_stage_order(): void
    {
        $this->getJson('/api/admin/lead-statuses')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.stage', 'new')
            ->assertJsonPath('data.0.name', 'Nova')
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonPath('data.5.stage', 'discarded');
    }

    public function test_admin_creates_edits_and_moves_the_default(): void
    {
        $id = $this->postJson('/api/admin/lead-statuses', [
            'name' => 'Sem resposta',
            'partner_name' => 'Em contato',
            'stage' => 'contacting',
            'color' => 'orange',
        ])->assertCreated()->assertJsonPath('data.is_default', false)->assertJsonPath('data.sort_order', 30)->json('data.id');

        // aparece depois de "Em contato", antes de Agendada
        $this->getJson('/api/admin/lead-statuses')->assertJsonPath('data.2.name', 'Sem resposta');

        $this->patchJson("/api/admin/lead-statuses/{$id}", ['stage' => 'new'])->assertJsonValidationErrors(['stage']);

        $this->patchJson("/api/admin/lead-statuses/{$id}", ['is_default' => true])->assertOk()->assertJsonPath('data.is_default', true);
        $this->assertSame($id, LeadStatus::defaultFor(LeadStage::Contacting)->id);
        $this->assertSame(1, LeadStatus::where('stage', 'contacting')->where('is_default', true)->count());

        // a padrão não pode ser desativada nem deixar de ser padrão direto
        $this->patchJson("/api/admin/lead-statuses/{$id}", ['active' => false])->assertJsonValidationErrors(['active']);
        $this->patchJson("/api/admin/lead-statuses/{$id}", ['is_default' => false])->assertJsonValidationErrors(['is_default']);

        $this->postJson('/api/admin/lead-statuses', [])->assertJsonValidationErrors(['name', 'partner_name', 'stage', 'color']);
    }

    public function test_deletes_only_unused_statuses(): void
    {
        $unused = LeadStatus::create(['name' => 'Teste', 'partner_name' => 'Teste', 'stage' => LeadStage::Contacting, 'color' => 'teal']);
        $used = LeadStatus::create(['name' => 'Usada', 'partner_name' => 'Usada', 'stage' => LeadStage::Contacting, 'color' => 'teal']);
        Lead::factory()->create(['lead_status_id' => $used->id]);

        $this->deleteJson("/api/admin/lead-statuses/{$used->id}")->assertJsonValidationErrors(['lead_status']);
        $this->deleteJson('/api/admin/lead-statuses/'.LeadStatus::defaultFor(LeadStage::New)->id)->assertJsonValidationErrors(['lead_status']);
        $this->deleteJson("/api/admin/lead-statuses/{$unused->id}")->assertNoContent();
    }

    public function test_field_agent_only_lists(): void
    {
        Passport::actingAs(User::factory()->team(UserRole::FieldAgent)->create());

        $this->getJson('/api/admin/lead-statuses')->assertOk();
        $this->postJson('/api/admin/lead-statuses', [])->assertForbidden();
    }
}
