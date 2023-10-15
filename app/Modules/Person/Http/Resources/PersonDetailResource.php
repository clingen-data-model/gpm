<?php

namespace App\Modules\Person\Http\Resources;

use App\Modules\Group\Http\Resources\MembershipResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['memberships'] = $this->whenLoaded('memberships', MembershipResource::collection($this->memberships));

        return $data;
    }
}
