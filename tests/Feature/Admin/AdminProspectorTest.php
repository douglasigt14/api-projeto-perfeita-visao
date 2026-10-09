<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\City;
use App\Models\Lead;
use App\Models\Prospector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AdminProspectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Os testes que entram como parceiro geram token.
        $this->artisan('passport:client', ['--personal' => true, '--name' => 'Testes', '--provider' => 'users', '--no-interaction' => true]);
        Passport::actingAs(User::factory()->team()->create());
    }

    public function test_lists_prospectors_with_filters_and_counts(): void
    {
        $iguatu = City::factory()->create();
        $maria = Prospector::factory()->create(['name' => 'Maria Souza', 'city_id' => $iguatu->id, 'phone_number' => '88999990001']);
        Prospector::factory()->create(['name' => 'Ana Lima', 'city_id' => $iguatu->id, 'blocked_at' => now()]);
        Prospector::factory()->create(['name' => 'Bruno']); // outra cidade
        Lead::factory()->create(['prospector_id' => $maria->id]);
        Lead::factory()->create(['prospector_id' => $maria->id, 'status' => LeadStatus::Attended, 'appointment_date' => '2026-10-15']);

        $this->getJson("/api/admin/prospectors?city_id={$iguatu->id}")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.name', 'Ana Lima')
            ->assertJsonPath('data.0.blocked', true)
            ->assertJsonPath('data.1.leads_count', 2)
            ->assertJsonPath('data.1.attended_count', 1)
            ->assertJsonPath('data.1.pix_key', $maria->pix_key);

        $this->getJson('/api/admin/prospectors?blocked=0&search=(88) 99999-0001')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $maria->id);

        $this->getJson('/api/admin/prospectors/options')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Ana Lima');
    }

    public function test_shows_a_prospector_with_leads_by_status(): void
    {
        $prospector = Prospector::factory()->create();
        Lead::factory()->count(2)->create(['prospector_id' => $prospector->id]);
        Lead::factory()->create(['prospector_id' => $prospector->id, 'status' => LeadStatus::Contacting]);

        $this->getJson("/api/admin/prospectors/{$prospector->id}")
            ->assertOk()
            ->assertJsonPath('data.leads_count', 3)
            ->assertJsonPath('data.leads_by_status.new', 2)
            ->assertJsonPath('data.leads_by_status.contacting', 1)
            ->assertJsonPath('data.leads_by_status.attended', 0);
    }

    public function test_admin_creates_a_prospector_who_can_log_in(): void
    {
        $city = City::factory()->create();

        $this->postJson('/api/admin/prospectors', [
            'name' => 'Carla Dias',
            'birth_date' => '1990-05-10',
            'phone_number' => '(88) 99888-7766',
            'city_id' => $city->id,
            'pix_key' => 'carla@exemplo.com',
            'instagram_handle' => '@carla.dias',
            'password' => 'senha12345',
        ])
            ->assertCreated()
            ->assertJsonPath('data.phone_number', '88998887766')
            ->assertJsonPath('data.instagram_handle', 'carla.dias')
            ->assertJsonPath('data.blocked', false);

        $this->postJson('/api/login', ['phone_number' => '88998887766', 'password' => 'senha12345'])->assertOk();
    }

    public function test_validation_and_duplicate_phone(): void
    {
        $existing = Prospector::factory()->create();
        $inactive = City::factory()->inactive()->create();

        $this->postJson('/api/admin/prospectors', [
            'name' => '',
            'birth_date' => now()->subYears(10)->toDateString(),
            'phone_number' => $existing->phone_number,
            'city_id' => $inactive->id,
            'password' => '123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'birth_date' => 'O parceiro precisa ter 18 anos ou mais.',
                'phone_number' => 'Este telefone já está cadastrado.',
                'city_id' => 'Escolha uma cidade ativa.',
                'pix_key',
                'password',
            ]);
    }

    public function test_admin_edits_and_phone_change_updates_the_login(): void
    {
        $user = User::factory()->create();
        $prospector = $user->prospector;

        $this->patchJson("/api/admin/prospectors/{$prospector->id}", ['name' => 'Novo Nome', 'phone_number' => $prospector->phone_number])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo Nome');

        $this->patchJson("/api/admin/prospectors/{$prospector->id}", ['phone_number' => '88977776666'])
            ->assertOk()
            ->assertJsonPath('data.phone_number', '88977776666');
        $this->assertSame('88977776666', $user->fresh()->phone_number);

        $this->patchJson("/api/admin/prospectors/{$prospector->id}", ['password' => 'qualquer123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_blocking_logs_out_and_prevents_login_and_new_leads(): void
    {
        $user = User::factory()->create();
        $prospector = $user->prospector;
        $user->createToken('app');

        $this->postJson("/api/admin/prospectors/{$prospector->id}/block")
            ->assertOk()
            ->assertJsonPath('data.blocked', true);
        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/login', ['phone_number' => $prospector->phone_number, 'password' => 'password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone_number' => 'Seu cadastro está bloqueado. Fale com a equipe Perfeita Visão.']);

        Passport::actingAs($user->fresh());
        $this->postJson('/api/leads', ['name' => 'X', 'phone_number' => '88911112222'])->assertForbidden();

        Passport::actingAs(User::factory()->team()->create());
        $this->deleteJson("/api/admin/prospectors/{$prospector->id}/block")
            ->assertOk()
            ->assertJsonPath('data.blocked', false);
        $this->postJson('/api/login', ['phone_number' => $prospector->phone_number, 'password' => 'password'])->assertOk();
    }

    public function test_admin_sets_a_new_password(): void
    {
        $user = User::factory()->create();
        $user->createToken('app');

        $this->putJson("/api/admin/prospectors/{$user->prospector_id}/password", ['password' => 'curta'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'A senha precisa ter pelo menos 8 caracteres.']);

        $this->putJson("/api/admin/prospectors/{$user->prospector_id}/password", ['password' => 'novasenha123'])->assertNoContent();

        $this->assertTrue(Hash::check('novasenha123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_field_agent_only_reads(): void
    {
        $prospector = Prospector::factory()->create();
        Passport::actingAs(User::factory()->team(UserRole::FieldAgent)->create());

        $this->getJson('/api/admin/prospectors')->assertOk();
        $this->getJson("/api/admin/prospectors/{$prospector->id}")->assertOk();
        $this->patchJson("/api/admin/prospectors/{$prospector->id}", ['name' => 'X'])->assertForbidden();
        $this->postJson("/api/admin/prospectors/{$prospector->id}/block")->assertForbidden();
        $this->putJson("/api/admin/prospectors/{$prospector->id}/password", ['password' => 'novasenha123'])->assertForbidden();
        $this->postJson('/api/admin/prospectors', [])->assertForbidden();
    }
}
