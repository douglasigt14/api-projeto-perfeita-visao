<?php

namespace App\Http\Resources\Admin;

use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Situação da indicação vista pela equipe.
 *
 * @mixin LeadStatus
 */
class LeadStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'partner_name' => $this->partner_name,
            'stage' => $this->stage,
            'color' => $this->color,
            'sort_order' => $this->sort_order,
            'is_default' => $this->is_default,
            'active' => $this->active,
            'leads_count' => $this->whenCounted('leads'),
        ];
    }
}
