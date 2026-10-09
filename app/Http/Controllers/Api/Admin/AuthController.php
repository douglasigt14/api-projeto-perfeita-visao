<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Resources\Admin\TeamUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login da equipe interna por e-mail e senha. Parceiros (sem papel) não entram aqui.
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->whereNotNull('role')->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha incorretos.',
            ]);
        }

        $token = $user->createToken($request->validated('device_name') ?: 'admin');

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token->accessToken,
            'expires_at' => $token->token->expires_at?->toIso8601String(),
            'user' => new TeamUserResource($user),
        ]);
    }

    public function me(Request $request): TeamUserResource
    {
        return new TeamUserResource($request->user());
    }

    public function logout(Request $request): Response
    {
        $request->user()->token()->revoke();

        return response()->noContent();
    }
}
