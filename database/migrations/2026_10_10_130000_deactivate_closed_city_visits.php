<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Atendimentos concluídos ou cancelados não recebem indicações: desliga os que ficaram ativos.
     */
    public function up(): void
    {
        DB::table('city_visits')->whereIn('status', ['completed', 'cancelled'])->update(['active' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
