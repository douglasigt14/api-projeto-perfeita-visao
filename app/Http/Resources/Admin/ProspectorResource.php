<?php

namespace App\Http\Resources\Admin;

use App\Enums\LeadStatus;
use App\Models\Prospector;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Parceiro (prospector) visto pela equipe.
 *
 * @mixin Prospector
 */
class ProspectorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birth_date' => $this->birth_date?->toDateString(),
            'phone_number' => $this->phone_number,
            'pix_key' => $this->pix_key,
            'instagram_handle' => $this->instagram_handle,
            'city' => $this->city->only(['id', 'name', 'state']),
            'blocked' => $this->isBlocked(),
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'leads_count' => $this->whenCounted('leads'),
            'attended_count' => $this->whenCounted('attendedLeads'),
            // Só no detalhe: quantas indicações em cada situação.
            'leads_by_status' => $this->when(
                $this->resource->relationLoaded('leads'),
                fn () => collect(LeadStatus::cases())->mapWithKeys(fn (LeadStatus $status) => [
                    $status->value => $this->leads->where('status', $status)->count(),
                ]),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
