<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Person\Http\Resources\InstitutionResource;

use App\Services\CocService;

class PersonAsMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $data = parent::toArray($request);
        $data = [
            'uuid' => $this->uuid,
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'timezone' => $this->timezone,
            'profile_photo' => $this->profile_photo,
            'legacy_credentials' => $this->legacy_credentials,
            'institution_id' => $this->institution_id,
            'country_id' => $this->country_id,
            'requires_core_member_attestation' => $this->requires_core_member_attestation,
            'core_member_attestation_completed' => $this->core_member_attestation_completed,
            'core_member_attestation_completion_date' => $this->core_member_attestation_completion_date,
        ];
        $data['institution'] = $this->whenLoaded('institution', fn() => new InstitutionResource($this->institution));
        $data['credentials'] = $this->whenLoaded('credentials');
        $data['expertises'] = $this->whenLoaded('expertises');
        $data['coc'] = $this->when(
            $this->resource->relationLoaded('latestCocAttestation'),
            fn () => app(CocService::class)->statusFor($this->resource)
        );

        return $data;
    }
}
