<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
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
        $data['roles'] = $this->whenLoaded('roles', RoleResource::collection($this->roles));
        $data['permissions'] = $this->whenLoaded('permissions', PermissionResource::collection($this->permissions));
        return $data;
    }
}
