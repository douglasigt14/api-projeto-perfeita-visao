<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    /**
     * Cidades ativas (para filtros e para o cadastro de atendimentos).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => City::where('active', true)->orderBy('name')->get(['id', 'name', 'state']),
        ]);
    }
}
