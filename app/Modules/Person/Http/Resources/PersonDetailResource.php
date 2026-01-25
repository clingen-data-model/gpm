<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use App\Modules\Group\Http\Resources\MembershipResource;
use App\Modules\Person\Models\Person;

class PersonDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(Request $request)
    {
        // we will handle memberships later... Shallow clone to avoid permanence of makeHidden
        $data = (clone $this)->makeHidden(['memberships'])->toArray();
        $user = $request->user();
        if ($user->id === $this?->user?->id || $user->hasAnyRole(['admin', 'super-admin'])) {
            foreach (Person::$contact_details_private_fields as $field) {
                $data[$field] = $this->$field;
            }
        }
        $data['memberships'] = $this->whenLoaded('memberships', fn() => MembershipResource::collection($this->memberships));
        return $data;
    }
}
