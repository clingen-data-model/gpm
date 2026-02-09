<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use App\Modules\Group\Http\Resources\MembershipResource;
use App\Modules\Person\Models\Person;
use App\Services\CocService;

class PersonDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(Request $request)
    {
        $data = parent::toArray($request);
        $user = $request->user();
        if ($user->id === $this?->user?->id || $user->hasAnyRole(['admin', 'super-admin'])) {
            foreach (Person::$contact_details_private_fields as $field) {
                $data[$field] = $this->$field;
            }
        }
        $data['coc'] = $this->when(
            $this->resource->relationLoaded('latestCocAttestation'),
            fn () => app(CocService::class)->statusFor($this->resource)
        );
        $data['memberships'] = $this->whenLoaded('memberships', MembershipResource::collection($this->memberships));
        return $data;
    }
}
