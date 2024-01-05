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

        // Only show demographics if user can manage people or for themselves
        $data['profile_demographics'] = $this->when($request->user()->can('people-manage') || $request->user()->isLinkedToPerson($request->person), $this->profile_demographics);
        return $data;
    }
}
