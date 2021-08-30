<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpertPanelResource extends JsonResource
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
        $data['cdwg'] = $this->whenLoaded('cdwg');
        $data['documents'] = $this->when($this->group->relationLoaded('documents'), $this->group->documents->toArray());
        $data['firstScopeDocument'] = $this->when($this->group->relationLoaded('documents'), $this->firstScopeDocument);
        $data['firstFinalDocument'] = $this->when($this->group->relationLoaded('documents'), $this->firstFinalDocument);
        $data['contacts'] = $this->when($this->relationLoaded('contacts'), $this->contacts->pluck('person'));

        return $data;
    }
}
