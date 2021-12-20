<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ExpertPanelCollection extends ResourceCollection
{
    public $collects = ExpertPanelResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        // $data['log_entries'] = $this->whenLoaded('group.logEntries', $this->group->logEntries);
        // $data['log_entries'] = $this->whenLoaded('group.latestLogEntry', $this->group->latestLogEntry);

        return $data;
    }
}
