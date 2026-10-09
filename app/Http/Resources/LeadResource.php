<?php

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lead
 */
class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'city' => $this->city->only(['id', 'name', 'state']),
            'visit' => $this->visit ? new CityVisitResource($this->visit) : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
