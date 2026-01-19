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
        $data['cois'] = $this->whenLoaded('cois');
        if ($this->relationLoaded('latestCoi')) {
            $data['latest_coi'] = $this->latestCoi;
            $data['latest_coi_id'] = $this->latestCoi?->id;
        }
        $data['latest_coi'] = $this->whenLoaded('latestCoi');
        if ($this->relationLoaded('group')) {
            $data['has_coi_requirement'] = $this->hasCoiRequirement;
        }
        if ($this->relationLoaded('cois') || $this->relationLoaded('latestCoi')) {
            $data['coi_needed'] = $this->coiNeeded;
            $data['coi_last_completed'] = $this->coiLastCompleted;
        }
        return $data;
    }
}
