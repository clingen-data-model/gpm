<?php

namespace App\Modules\User\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\RoleResource;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Person\Http\Resources\PersonDetailResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['roles'] = $this->whenLoaded('roles', RoleResource::collection($this->roles));
        $data['memberships'] = $this->whenLoaded('person', MemberResource::collection($this->person->memberships));
        $data['person'] = $this->whenLoaded('person', new PersonDetailResource($this->person));
        $data['is_impersonating'] = $this->isImpersonating;

        return $data;
    }
}
