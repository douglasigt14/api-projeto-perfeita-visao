<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parceiro bloqueado pela equipe: não entra no app nem envia indicações.
     */
    public function up(): void
    {
        Schema::table('prospectors', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('instagram_handle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospectors', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
        });
    }
};
