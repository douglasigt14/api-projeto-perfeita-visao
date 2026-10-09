<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleLeadRequest;
use App\Http\Requests\Admin\StoreLeadContactRequest;
use App\Http\Requests\Admin\UpdateLeadStatusRequest;
use App\Http\Resources\Admin\LeadContactResource;
use App\Http\Resources\Admin\LeadResource;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    /**
     * Lista global de indicações, paginada, com filtros e busca por nome ou telefone. Mais recentes primeiro.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'city_id' => ['nullable', 'integer'],
            'city_visit_id' => ['nullable', 'integer'],
            'prospector_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'appointment_date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $leads = Lead::query()
            ->with(['city', 'visit', 'prospector'])
            ->withCount('contacts')
            ->when($filters['city_id'] ?? null, fn ($q, $id) => $q->where('city_id', $id))
            ->when($filters['city_visit_id'] ?? null, fn ($q, $id) => $q->where('city_visit_id', $id))
            ->when($filters['prospector_id'] ?? null, fn ($q, $id) => $q->where('prospector_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['appointment_date'] ?? null, fn ($q, $date) => $q->whereDate('appointment_date', $date))
            ->when(trim($filters['search'] ?? ''), function ($q, string $search) {
                $digits = preg_replace('/\D/', '', $search);
                $q->where(function ($q) use ($search, $digits) {
                    $q->where('name', 'like', "%{$search}%");
                    if ($digits !== '') {
                        $q->orWhere('phone_number', 'like', "%{$digits}%");
                    }
                });
            })
            ->latest()
            ->latest('id')
            ->paginate($filters['per_page'] ?? 25);

        return LeadResource::collection($leads);
    }

    public function show(Lead $lead): LeadResource
    {
        return new LeadResource($lead->load(['city', 'visit', 'prospector', 'contacts.user']));
    }

    /**
     * Marca o dia do exame (status vira "Agendada") ou desmarca com null (volta para "Em contato").
     */
    public function schedule(ScheduleLeadRequest $request, Lead $lead): LeadResource
    {
        $date = $request->validated('appointment_date');

        $lead->update([
            'appointment_date' => $date,
            'status' => $date ? LeadStatus::Scheduled : LeadStatus::Contacting,
        ]);

        return $this->show($lead);
    }

    /**
     * Muda a situação. Voltar para Nova, Em contato ou Descartada desmarca o exame.
     */
    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): LeadResource
    {
        $status = LeadStatus::from($request->validated('status'));

        $lead->update([
            'status' => $status,
            'appointment_date' => $status->needsAppointment() ? $lead->appointment_date : null,
        ]);

        return $this->show($lead);
    }

    /**
     * Registra um contato da equipe. Indicação "Nova" passa para "Em contato".
     */
    public function storeContact(StoreLeadContactRequest $request, Lead $lead): JsonResponse
    {
        $contact = $lead->contacts()->create([...$request->validated(), 'user_id' => $request->user()->id]);

        if ($lead->status === LeadStatus::New) {
            $lead->update(['status' => LeadStatus::Contacting]);
        }

        return (new LeadContactResource($contact->load('user')))->response()->setStatusCode(201);
    }
}
