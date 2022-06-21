<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
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
        $data['cois'] = $this->whenLoaded('cois', $this->cois);
        $data['latest_coi'] = $this->whenLoaded('latestCoi', $this->latestCoi);
        if ($this->relationLoaded('cois') || $this->relationLoaded('latestCoi')) {
            $data['coi_needed'] = $this->coiNeeded;
            $data['has_coi_requirement'] = $this->hasCoiRequirement;
            $data['coi_last_completed'] = $this->coiLastCompleted;
        }
        return $data;
    }
}
