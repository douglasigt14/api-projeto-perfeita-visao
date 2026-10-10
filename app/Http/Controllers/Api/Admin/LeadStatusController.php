<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadStatusRequest;
use App\Http\Resources\Admin\LeadStatusResource;
use App\Models\LeadStatus;
use App\Models\LeadStatusChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Situações da indicação. Admin e atendente listam (selos, filtros, troca de situação); só o Admin cadastra e edita.
 */
class LeadStatusController extends Controller
{
    /**
     * Todas (inclusive desativadas), na ordem das etapas e depois na ordem escolhida.
     */
    public function index(): AnonymousResourceCollection
    {
        return LeadStatusResource::collection(LeadStatus::query()->ordered()->withCount('leads')->get());
    }

    public function store(LeadStatusRequest $request): JsonResponse
    {
        $status = DB::transaction(fn () => $this->save(new LeadStatus, $request));

        return (new LeadStatusResource($status->loadCount('leads')))->response()->setStatusCode(201);
    }

    public function update(LeadStatusRequest $request, LeadStatus $leadStatus): LeadStatusResource
    {
        DB::transaction(fn () => $this->save($leadStatus, $request));

        return new LeadStatusResource($leadStatus->loadCount('leads'));
    }

    /**
     * Só apaga situação que nunca foi usada (nem no histórico). Usada → desativar.
     */
    public function destroy(LeadStatus $leadStatus): Response
    {
        $used = $leadStatus->leads()->withTrashed()->exists()
            || LeadStatusChange::where('from_lead_status_id', $leadStatus->id)->orWhere('to_lead_status_id', $leadStatus->id)->exists();

        if ($leadStatus->is_default || $used) {
            throw ValidationException::withMessages([
                'lead_status' => $leadStatus->is_default
                    ? 'A situação padrão da etapa não pode ser apagada.'
                    : 'Esta situação já foi usada em indicações. Desative em vez de apagar.',
            ]);
        }

        $leadStatus->delete();

        return response()->noContent();
    }

    /**
     * Grava e, se virou a padrão, tira a marca da antiga padrão da mesma etapa.
     */
    private function save(LeadStatus $status, LeadStatusRequest $request): LeadStatus
    {
        $status->fill($request->validated());
        if (! $status->exists && ! $request->has('sort_order')) {
            $status->sort_order = (int) LeadStatus::where('stage', $status->stage)->max('sort_order') + 10;
        }
        $status->save();

        if ($status->is_default) {
            LeadStatus::where('stage', $status->stage)->whereKeyNot($status->id)->update(['is_default' => false]);
        }

        return $status->refresh();
    }
}
