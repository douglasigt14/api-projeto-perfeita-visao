<?php

namespace App\Http\Resources\Admin;

use App\Models\LeadContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeadContact
 */
class LeadContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'result' => $this->result,
            'notes' => $this->notes,
            'user' => $this->user?->only(['id', 'name']),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
