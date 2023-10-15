<?php

namespace App\Modules\Group\Http\Resources;

use App\Http\Resources\ExpertPanelResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['expert_panel'] = $this->whenLoaded('expertPanel', new ExpertPanelResource($this->expertPanel));
        $data['type'] = $this->whenLoaded('type', new GroupTypeResource($this->type));
        $data['status'] = $this->whenLoaded('status', new GroupStatusResource($this->status));
        $data['members'] = $this->whenLoaded('members', MemberResource::collection($this->members));
        $data['coordinators'] = $this->whenLoaded('coordinators', MemberResource::collection($this->members));
        // $data['parent'] = $this->whenLoaded('parent', GroupResource::collection($this->parent));

        unset($data['members'], $data['created_at'], $data['deleted_at']);

        return $data;
    }
}
