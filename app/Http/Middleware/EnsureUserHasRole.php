<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Libera a rota só para usuários da equipe com um dos papéis informados (ex.: role:admin,field_agent).
 * Sem papéis na rota, basta ser da equipe. Parceiros recebem 403.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = $roles === []
            ? $user?->isTeamMember()
            : $user?->hasRole(...array_map(fn (string $role) => UserRole::from($role), $roles));

        abort_unless($allowed, 403, 'Você não tem permissão para acessar esta área.');

        return $next($request);
    }
}
