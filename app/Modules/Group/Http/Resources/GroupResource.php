<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\ExpertPanelResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Http\Resources\MemberResource;
use App\Modules\Group\Http\Resources\GroupStatusResource;
use App\Modules\Group\Http\Resources\GroupTypeResource;
use App\Modules\Group\Http\Resources\GroupVisibilityResource;

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

        $data['expert_panel'] = $this->whenLoaded('expertPanel', fn() => new ExpertPanelResource($this->expertPanel));
        $data['type'] = $this->whenLoaded('type', fn() => new GroupTypeResource($this->type));
        $data['status'] = $this->whenLoaded('status', fn() => new GroupStatusResource($this->status));
        // Noticed that this was loaded here, but unset later. Leaving commented for now.
        // $data['members'] = $this->whenLoaded('members', fn() => MemberResource::collection($this->members));
        // This next bit was weird, too, since it loaded "members" data as "coordinators".
        $data['coordinators'] = $this->whenLoaded('coordinators', fn() => MemberResource::collection($this->coordinators));
        $data['visibility'] = $this->whenLoaded('groupVisibility', fn() => new GroupVisibilityResource($this->groupVisibility));
        $data['is_ep'] = $this->isEp;
        $data['is_vcep'] = $this->isVcep;
        $data['is_gcep'] = $this->isGcep;
        $data['is_scvcep'] = $this->isScvcep;
        $data['is_vcep_or_scvcep'] = $this->isVcepOrScvcep;
        $data['caption'] = $this->caption;
        $data['icon_url'] = $this->icon_url;
        $data['is_working_group'] = $this->is_working_group ?? false;
        $data['is_private'] = $this->is_private ?? false;
        // $data['parent'] = $this->whenLoaded('parent', GroupResource::collection($this->parent));


        unset($data['members'], $data['created_at'], $data['deleted_at']);
        return $data;
    }
}
