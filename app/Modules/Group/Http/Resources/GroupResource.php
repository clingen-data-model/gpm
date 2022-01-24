<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MemberResource;

class GroupResource extends JsonResource
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
        $data['type'] = $this->whenLoaded('type', new GroupTypeResource($data['type']));
        $data['members'] = $this->whenLoaded('members', MemberResource::collection($this->members));
        $data['coordinators'] = $this->whenLoaded('coordinators', MemberResource::collection($this->members));
        return $data;
    }
}
