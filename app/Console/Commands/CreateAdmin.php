<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Cria um administrador do painel da equipe (o primeiro; os outros podem ser criados na tela Usuários).
 */
#[Signature('admin:create {--name=} {--email=} {--password=}')]
#[Description('Cria um usuário Administrador do painel da equipe')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?: text('Nome', required: true),
            'email' => mb_strtolower(trim($this->option('email') ?: text('E-mail', required: true))),
            'password' => $this->option('password') ?: password('Senha (mín. 8 caracteres)', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create([...$data, 'role' => UserRole::Admin]);

        $this->info("Administrador {$user->name} <{$user->email}> criado.");

        return self::SUCCESS;
    }
}
