<?php

namespace App\Http\Controllers\Api;

use App\Enums\LeadStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\CityVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LeadController extends Controller
{
    /**
     * Indicações do prospector logado, da mais recente para a mais antiga.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $prospector = $request->user()->prospector;
        abort_if($prospector === null, 403);

        return LeadResource::collection($prospector->leads()->with(['city', 'visit', 'status'])->latest()->latest('id')->get());
    }

    /**
     * Nova indicação do prospector logado. De parceiro confiável, já nasce Agendada
     * no 1º dia livre do atendimento (a equipe pode trocar o dia depois).
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $prospector = $request->user()->prospector;
        $data = $request->validated();

        if ($prospector->isTrusted()) {
            $data['stage'] = LeadStage::Scheduled;
            $data['appointment_date'] = CityVisit::findOrFail($data['city_visit_id'])->firstAvailableDay();
        }

        $lead = $prospector->leads()->create($data);

        return (new LeadResource($lead->load(['city', 'visit', 'status'])))->response()->setStatusCode(201);
    }

    /**
     * Apaga (soft delete) uma indicação do prospector logado. Indicação de outro prospector → 404.
     * Só enquanto está "Nova": depois que a equipe começou a trabalhar nela, não dá mais (422).
     */
    public function destroy(Request $request, int $lead): Response
    {
        $prospector = $request->user()->prospector;
        abort_if($prospector === null, 403);

        $lead = $prospector->leads()->findOrFail($lead);

        if ($lead->stage !== LeadStage::New) {
            throw ValidationException::withMessages([
                'lead' => 'A equipe já está cuidando desta indicação, então ela não pode mais ser apagada.',
            ]);
        }

        $lead->delete();

        return response()->noContent();
    }
}
