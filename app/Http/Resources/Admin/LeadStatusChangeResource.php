<?php

namespace App\Http\Resources\Admin;

use App\Models\LeadStatusChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Uma mudança de situação no histórico da indicação (from vazio = indicação recebida).
 *
 * @mixin LeadStatusChange
 */
class LeadStatusChangeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from' => $this->from ? new LeadStatusResource($this->from) : null,
            'to' => new LeadStatusResource($this->to),
            // Parceiro (ao indicar) não tem nome em users: usa o do prospector.
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name ?? $this->user->prospector?->name,
                'is_partner' => $this->user->role === null,
            ] : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
