<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\ExpertPanelResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Http\Resources\GroupStatusResource;

class GroupResource extends JsonResource
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

        $data['expert_panel'] = $this->whenLoaded('expertPanel', new ExpertPanelResource($this->expertPanel));
        $data['type'] = $this->whenLoaded('type', new GroupTypeResource($this->type));
        $data['status'] = $this->whenLoaded('status', new GroupStatusResource($this->status));
        $data['members'] = $this->whenLoaded('members', MemberResource::collection($this->members));
        $data['coordinators'] = $this->whenLoaded('coordinators', MemberResource::collection($this->members));
        $data['is_ep'] = $this->isEp;
        $data['is_vcep'] = $this->isVcep;
        $data['is_gcep'] = $this->isGcep;
        $data['is_scvcep'] = $this->isScvcep;
        $data['is_vcep_or_scvcep'] = $this->isVcepOrScvcep;
        // $data['parent'] = $this->whenLoaded('parent', GroupResource::collection($this->parent));


        unset($data['members'], $data['created_at'], $data['deleted_at']);
        return $data;
    }
}
