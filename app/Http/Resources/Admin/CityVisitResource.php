<?php

namespace App\Http\Resources\Admin;

use App\Models\CityVisit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CityVisit
 */
class CityVisitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'visit_date' => $this->visit_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'active' => $this->active,
            'city' => $this->city->only(['id', 'name', 'state']),
            'leads_count' => $this->whenCounted('leads'),
            'scheduled_count' => $this->whenCounted('scheduledLeads'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
