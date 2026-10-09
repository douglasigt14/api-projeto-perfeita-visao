<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usuários da equipe interna: entram por e-mail e têm um papel (role). Parceiros seguem com telefone e role nulo.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('prospector_id');
            $table->string('email')->nullable()->unique()->after('name');
            $table->string('role')->nullable()->index()->after('email');
            $table->string('phone_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropUnique(['email']);
            $table->dropColumn(['name', 'email', 'role']);
            $table->string('phone_number')->nullable(false)->change();
        });
    }
};
