<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    /**
     * Lista de cidades ativas para o formulário de cadastro.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => City::active()->orderBy('name')->get(['id', 'name', 'state']),
        ]);
    }
}
