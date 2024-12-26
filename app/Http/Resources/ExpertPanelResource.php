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
        unset($data['cdwg_id'], $data['created_at'], $data['updated_at'], $data['']);
        $data['cdwg'] = $this->whenLoaded('cdwg');
        $data['working_name'] = $this->long_base_name;
        $data['group'] = $this->whenLoaded('group', $this->group);
        $data['log_entries'] = $this->whenLoaded('group.logEntries');
        $data['latest_log_entry'] = $this->whenLoaded('group.latestLogEntry');
        $data['latest_submission'] = $this->whenLoaded('group.latestSubmission');
        $data['documents'] = $this->whenLoaded('group.documents');
        $data['is_vcep'] = $this->group->isVcep;
        $data['is_gcep'] = $this->group->isGcep;
        $data['is_scvcep'] = $this->group->isScvcep;

        return $data;
    }
}
