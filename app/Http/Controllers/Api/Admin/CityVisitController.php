<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\CityVisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityVisitRequest;
use App\Http\Resources\Admin\CityVisitResource;
use App\Models\CityVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CityVisitController extends Controller
{
    /**
     * Atendimentos com filtros (cidade, situação, ativo e período), do mais recente para o mais antigo.
     * O período (from/to) pega todo atendimento que encosta no intervalo — serve para o calendário.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'city_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(CityVisitStatus::class)],
            'active' => ['nullable', 'boolean'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $visits = CityVisit::query()
            ->with('city')
            ->withCount(['leads', 'scheduledLeads'])
            ->when($filters['city_id'] ?? null, fn ($q, $cityId) => $q->where('city_id', $cityId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(isset($filters['active']), fn ($q) => $q->where('active', $request->boolean('active')))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereRaw('COALESCE(end_date, visit_date) >= ?', [$from]))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('visit_date', '<=', $to))
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->get();

        return CityVisitResource::collection($visits);
    }

    public function show(CityVisit $cityVisit): CityVisitResource
    {
        return new CityVisitResource($cityVisit->load('city')->loadCount(['leads', 'scheduledLeads']));
    }

    public function store(CityVisitRequest $request): JsonResponse
    {
        $visit = CityVisit::create($request->validated());

        return (new CityVisitResource($visit->refresh()->load('city')->loadCount(['leads', 'scheduledLeads'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Edita, ativa/desativa (active) ou cancela (status = cancelled) um atendimento.
     */
    public function update(CityVisitRequest $request, CityVisit $cityVisit): CityVisitResource
    {
        $cityVisit->update($request->validated());

        return new CityVisitResource($cityVisit->refresh()->load('city')->loadCount(['leads', 'scheduledLeads']));
    }
}
