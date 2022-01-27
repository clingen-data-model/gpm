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

        $data['working_name'] = $this->long_base_name;

        if ($this->relationLoaded('group')) {
            if (!$data['working_name']) {
                $data['working_name'] = $this->group->name;
            }

            $data['log_entries'] = $this->whenLoaded('group.logEntries');
            $data['latest_log_entry'] = $this->whenLoaded('group.latestLogEntry');

            $data['documents'] = $this->when(
                $this->group->relationLoaded('documents'),
                $this->group->documents->toArray()
            );

            $data['firstScopeDocument'] = $this->when(
                $this->group->relationLoaded('documents'),
                $this->firstScopeDocument
            );
            $data['firstFinalDocument'] = $this->when(
                $this->group->relationLoaded('documents'),
                $this->firstFinalDocument
            );
        }
        $data['contacts'] = $this->when($this->relationLoaded('contacts'), $this->contacts->pluck('person'));

        return $data;
    }
}
