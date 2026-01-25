<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\RoleResource;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Person\Http\Resources\PersonAsMemberResource;

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
        unset(
            $data['created_at'],
            $data['updated_at'],
            $data['parent_id'],
            $data['deleted_at'],
            $data['cois'] // really do need this.
        );


        $data['person'] = $this->whenLoaded('person', fn() => new PersonAsMemberResource($this->person));
        $data['roles'] = $this->whenLoaded('roles', fn() => RoleResource::collection($this->roles));
        $data['permissions'] = $this->whenLoaded('permissions', fn() => PermissionResource::collection($this->permissions));
        if ($this->relationLoaded('latestCoi')) {
            $data['coi_last_completed'] = $this->coi_last_completed;
            $data['latest_coi_id'] = $this->latestCoi ? $this->latestCoi->id : null;
        }

        if ($this->relationLoaded('person.expertises')) {
            $data['expertise'] = $this->expertise;
        }

        return $data;
    }
}
