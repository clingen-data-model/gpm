<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Models\Group;

class GroupExternalResource extends JsonResource
{
    function __construct(Group $group)
    {
        parent::__construct($group);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->groupStatus->name,
            'type' => $this->type->name,
            'members' => $this->members->map(function ($member) {
                $p = $member->person;
                return [
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'roles' => $member->roles->map(function ($role) {
                        return $role->display_name;
                    }),
                ];
            }),
        ];

        if ($this->isEp) {
            $ep = $this->expertPanel;
            $data += [
                'expert_panel' => [
                    'uuid' => $ep->uuid,
                    'name' => $ep->long_base_name,
                    'short_name' => $ep->short_base_name,
                    'scope_description' => $ep->scope_description,
                    'membership_description' => $ep->membership_description,
                    'type' => $ep->type->name,
                    'step_1_approval_date' => $ep->step_1_approval_date,
                    'step_2_approval_date' => $ep->step_2_approval_date,
                    'step_3_approval_date' => $ep->step_3_approval_date,
                    'step_4_approval_date' => $ep->step_4_approval_date,
                    'date_completed' => $ep->date_completed,
                    'current_step' => $ep->current_step,
                ],
            ];
        }

        if ($this->parent) {
            $data += [
                'parent' => [
                    'uuid' => $this->parent->uuid,
                    'name' => $this->parent->name,
                ],
            ];
        }
        
        return $data;
    }
}
