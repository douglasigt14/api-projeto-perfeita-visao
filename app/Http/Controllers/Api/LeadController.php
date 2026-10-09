<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Resources\LeadResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LeadController extends Controller
{
    /**
     * Indicações do prospector logado, da mais recente para a mais antiga.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $prospector = $request->user()->prospector;
        abort_if($prospector === null, 403);

        return LeadResource::collection($prospector->leads()->with(['city', 'visit'])->latest()->latest('id')->get());
    }

    /**
     * Nova indicação do prospector logado.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $request->user()->prospector->leads()->create($request->validated());

        return (new LeadResource($lead->load(['city', 'visit'])))->response()->setStatusCode(201);
    }

    /**
     * Apaga (soft delete) uma indicação do prospector logado. Indicação de outro prospector → 404.
     */
    public function destroy(Request $request, int $lead): Response
    {
        $prospector = $request->user()->prospector;
        abort_if($prospector === null, 403);

        $prospector->leads()->findOrFail($lead)->delete();

        return response()->noContent();
    }
}
