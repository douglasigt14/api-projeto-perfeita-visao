<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamUserRequest;
use App\Http\Resources\Admin\TeamUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Usuários da equipe interna (só Admin). Parceiros não aparecem nem podem ser editados aqui.
 */
class TeamUserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return TeamUserResource::collection(User::whereNotNull('role')->orderBy('name')->get());
    }

    public function store(TeamUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new TeamUserResource($user))->response()->setStatusCode(201);
    }

    public function update(TeamUserRequest $request, User $user): TeamUserResource
    {
        abort_unless($user->isTeamMember(), 404);

        $data = array_filter($request->validated(), fn ($value, $key) => $key !== 'password' || filled($value), ARRAY_FILTER_USE_BOTH);

        if ($request->user()->is($user) && isset($data['role']) && $data['role'] !== $user->role->value) {
            throw ValidationException::withMessages(['role' => 'Você não pode mudar o seu próprio papel.']);
        }

        $user->update($data);

        return new TeamUserResource($user);
    }

    /**
     * Remove o usuário da equipe e derruba as sessões dele. Não dá para remover a si mesmo.
     */
    public function destroy(Request $request, User $user): Response
    {
        abort_unless($user->isTeamMember(), 404);

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['user' => 'Você não pode remover o seu próprio usuário.']);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
