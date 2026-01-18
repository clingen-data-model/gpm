<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\RoleResource;
use App\Http\Resources\ExpertPanelResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Http\Resources\GroupStatusResource;

class GroupSummaryResource extends JsonResource
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

        $data['expert_panel'] = $this->whenLoaded('expertPanel', fn() => new ExpertPanelResource($this->expertPanel));
        $data['type'] = $this->whenLoaded('type', fn() => new GroupTypeResource($this->type));
        $data['status'] = $this->whenLoaded('status', fn() => new GroupStatusResource($this->status));
        $data['is_ep'] = $this->isEp;
        $data['is_vcep'] = $this->isVcep;
        $data['is_gcep'] = $this->isGcep;
        $data['is_scvcep'] = $this->isScvcep;
        $data['is_vcep_or_scvcep'] = $this->isVcepOrScvcep;


        unset($data['created_at'], $data['deleted_at']);
        return $data;
    }
}
