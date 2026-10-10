<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\CityVisitStatus;
use App\Enums\LeadStage;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityVisit;
use App\Models\Lead;
use App\Models\Prospector;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private const RANKING_SIZE = 10;

    /**
     * Visão geral do período (padrão: o mês atual inteiro), opcionalmente de uma cidade.
     * Indicações e ranking contam as indicações recebidas no período (created_at);
     * atendimentos contam os que encostam no período (como no calendário).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'city_id' => ['nullable', 'integer'],
        ], [
            'to.after_or_equal' => 'A data final não pode ser antes da inicial.',
        ]);

        $timezone = config('app.business_timezone');
        $today = Carbon::now($timezone);
        $from = isset($filters['from']) ? Carbon::parse($filters['from'], $timezone) : $today->copy()->startOfMonth();
        $to = isset($filters['to']) ? Carbon::parse($filters['to'], $timezone) : $today->copy()->endOfMonth();
        $cityId = $filters['city_id'] ?? null;

        // Limites do período em UTC, que é como o created_at está gravado.
        $createdBetween = [$from->copy()->startOfDay()->utc(), $to->copy()->endOfDay()->utc()];

        $leads = fn (): Builder => Lead::query()
            ->whereBetween('leads.created_at', $createdBetween)
            ->when($cityId, fn ($q) => $q->where('leads.city_id', $cityId));

        $byStage = $leads()->selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total', 'stage');
        $leadsByStage = collect(LeadStage::cases())
            ->mapWithKeys(fn (LeadStage $stage) => [$stage->value => (int) ($byStage[$stage->value] ?? 0)]);

        $visitsByStatus = CityVisit::query()
            ->whereRaw('COALESCE(end_date, visit_date) >= ?', [$from->toDateString()])
            ->where('visit_date', '<=', $to->toDateString())
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json(['data' => [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'leads' => [
                'total' => $leadsByStage->sum(),
                'with_appointment' => $leadsByStage->only($this->appointmentStages())->sum(),
                'attended' => $leadsByStage[LeadStage::Attended->value],
                'by_stage' => $leadsByStage,
            ],
            'leads_by_city' => $this->leadsByCity($leads()),
            'visits' => [
                'total' => $visitsByStatus->sum(),
                'by_status' => collect(CityVisitStatus::cases())
                    ->mapWithKeys(fn (CityVisitStatus $status) => [$status->value => (int) ($visitsByStatus[$status->value] ?? 0)]),
            ],
            'prospectors' => [
                'active' => $leads()->distinct()->count('prospector_id'),
                'new' => Prospector::query()
                    ->whereBetween('created_at', $createdBetween)
                    ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
                    ->count(),
            ],
            'ranking' => $this->ranking($leads()),
        ]]);
    }

    /**
     * @return list<string>
     */
    private function appointmentStages(): array
    {
        return collect(LeadStage::cases())
            ->filter(fn (LeadStage $stage) => $stage->needsAppointment())
            ->map(fn (LeadStage $stage) => $stage->value)
            ->values()
            ->all();
    }

    /**
     * Indicações por cidade, da que mais recebeu para a que menos recebeu.
     *
     * @param  Builder<Lead>  $leads
     * @return list<array<string, mixed>>
     */
    private function leadsByCity(Builder $leads): array
    {
        $rows = $leads
            ->selectRaw('city_id, COUNT(*) as total, SUM(CASE WHEN stage = ? THEN 1 ELSE 0 END) as attended', [LeadStage::Attended->value])
            ->groupBy('city_id')
            ->orderByDesc('total')
            ->get();

        $cities = City::whereIn('id', $rows->pluck('city_id'))->get(['id', 'name', 'state'])->keyBy('id');

        return $rows->map(fn ($row) => [
            'city' => $cities[$row->city_id]->only(['id', 'name', 'state']),
            'total' => (int) $row->total,
            'attended' => (int) $row->attended,
        ])->values()->all();
    }

    /**
     * Parceiros que mais trouxeram gente ao exame (compareceu), depois os que mais indicaram.
     *
     * @param  Builder<Lead>  $leads
     * @return list<array<string, mixed>>
     */
    private function ranking(Builder $leads): array
    {
        $rows = $leads
            ->selectRaw(
                'prospector_id, COUNT(*) as leads_count,'
                .' SUM(CASE WHEN stage IN (?, ?, ?) THEN 1 ELSE 0 END) as with_appointment_count,'
                .' SUM(CASE WHEN stage = ? THEN 1 ELSE 0 END) as attended_count',
                [...$this->appointmentStages(), LeadStage::Attended->value],
            )
            ->groupBy('prospector_id')
            ->orderByDesc('attended_count')
            ->orderByDesc('leads_count')
            ->orderBy('prospector_id')
            ->limit(self::RANKING_SIZE)
            ->get();

        $prospectors = Prospector::with('city:id,name,state')->whereIn('id', $rows->pluck('prospector_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($prospectors) {
            $prospector = $prospectors[$row->prospector_id];

            return [
                'prospector' => [
                    'id' => $prospector->id,
                    'name' => $prospector->name,
                    'phone_number' => $prospector->phone_number,
                    'city' => $prospector->city->only(['id', 'name', 'state']),
                ],
                'leads_count' => (int) $row->leads_count,
                'with_appointment_count' => (int) $row->with_appointment_count,
                'attended_count' => (int) $row->attended_count,
            ];
        })->values()->all();
    }
}
