<?php

namespace App\Modules\User\Http\Resources;

use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MemberResource;

class CurrentUserResource extends JsonResource
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
        $rolePermissions = $this->relationLoaded('roles.permissions')
                                ? $this->roles->pluck('permissions')->flatten()
                                : collect();
        $data['memberships'] = $this->whenLoaded('person', MemberResource::collection($this->person->memberships));
        // unset($data['person']['memberships']);

        return $data;
    }
}
