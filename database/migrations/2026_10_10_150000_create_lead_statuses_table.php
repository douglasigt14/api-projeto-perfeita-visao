<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Situações da indicação editáveis pela equipe, cada uma dentro de uma etapa fixa (LeadStage).
     * leads.status vira leads.stage (preenchida pelo model) e entra leads.lead_status_id.
     * Histórico de mudanças em lead_status_changes.
     */
    public function up(): void
    {
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('partner_name');
            $table->string('stage')->index();
            $table->string('color');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['new', 'Nova', 'Recebida', 'sky'],
            ['contacting', 'Em contato', 'Em contato', 'amber'],
            ['scheduled', 'Agendada', 'Exame marcado', 'emerald'],
            ['attended', 'Compareceu', 'Fez o exame', 'green'],
            ['no_show', 'Não compareceu', 'Não compareceu', 'rose'],
            ['discarded', 'Descartada', 'Não seguiu', 'zinc'],
        ];
        foreach ($defaults as $order => [$stage, $name, $partnerName, $color]) {
            DB::table('lead_statuses')->insert([
                'name' => $name,
                'partner_name' => $partnerName,
                'stage' => $stage,
                'color' => $color,
                'sort_order' => ($order + 1) * 10,
                'is_default' => true,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->renameColumn('status', 'stage');
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('lead_status_id')->nullable()->after('stage')->constrained('lead_statuses');
        });

        // Liga cada indicação à situação padrão da etapa que ela já tinha.
        foreach (DB::table('lead_statuses')->get(['id', 'stage']) as $status) {
            DB::table('leads')->where('stage', $status->stage)->update(['lead_status_id' => $status->id]);
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('lead_status_id')->nullable(false)->change();
        });

        Schema::create('lead_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_lead_status_id')->nullable()->constrained('lead_statuses');
            $table->foreignId('to_lead_status_id')->constrained('lead_statuses');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_status_changes');
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lead_status_id');
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->renameColumn('stage', 'status');
        });
        Schema::dropIfExists('lead_statuses');
    }
};
