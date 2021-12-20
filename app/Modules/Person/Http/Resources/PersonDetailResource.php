<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MembershipResource;

class PersonDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['memberships'] = $this->whenLoaded('memberships', MembershipResource::collection($this->memberships));
        return $data;
    }
}
