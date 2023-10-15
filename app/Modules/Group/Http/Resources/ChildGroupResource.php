<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChildGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->displayName,
            'members_count' => $this->members_count,
            'coordinators' => $this->whenLoaded('coordinators', $this->coordinators),
            'chairs' => $this->whenLoaded('chairs', $this->chairs),
            'type' => $this->whenLoaded('type', $this->type),
            'status' => $this->whenLoaded('status', $this->status),
        ];
    }
}
