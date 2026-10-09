<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prospector;
use Illuminate\Http\JsonResponse;

class ProspectorController extends Controller
{
    /**
     * Parceiros com a quantidade de indicações (para o filtro da lista de indicações).
     */
    public function index(): JsonResponse
    {
        $prospectors = Prospector::with('city:id,name,state')
            ->withCount('leads')
            ->orderBy('name')
            ->get()
            ->map(fn (Prospector $prospector) => [
                'id' => $prospector->id,
                'name' => $prospector->name,
                'phone_number' => $prospector->phone_number,
                'city' => $prospector->city->only(['id', 'name', 'state']),
                'leads_count' => $prospector->leads_count,
            ]);

        return response()->json(['data' => $prospectors]);
    }
}
