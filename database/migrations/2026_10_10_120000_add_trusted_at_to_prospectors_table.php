<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parceiro confiável: as indicações dele já nascem agendadas.
     */
    public function up(): void
    {
        Schema::table('prospectors', function (Blueprint $table) {
            $table->timestamp('trusted_at')->nullable()->after('blocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospectors', function (Blueprint $table) {
            $table->dropColumn('trusted_at');
        });
    }
};
