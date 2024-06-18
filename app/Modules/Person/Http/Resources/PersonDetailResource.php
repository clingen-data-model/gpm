<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MembershipResource;
use Illuminate\Support\Facades\Gate;

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
        if (Gate::denies('viewDemographics', $this->resource)) {
            foreach ($this->resource->demographicsFields as $field) {
                unset($data[$field]);
            }
            return $data;
        }
        return $data;
    }
}
