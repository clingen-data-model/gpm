<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Models\Group;

class GroupExternalResource extends JsonResource
{
    public static $wrap = 'gpm_group';

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
            'status_date' => $this->groupStatus->updated_at,
            'type' => $this->type->name,
            'members' => $this->members()->with(['person', 'person.credentials', 'roles', 'latestCoi'])->get()->map(function ($member) {
                $p = $member->person;
                $personData = [
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'credentials' => $p->credentials->map(function ($credential) {
                        return $credential->name;
                    }),
                    'uuid' => $p->uuid,
                    'roles' => $member->roles->map(function ($role) {
                        return $role->display_name;
                    }),
                    'last_coi_completion_date' => $member->latestCoi?->completed_at,
                ];
                if ($p->profile_photo) {
                    $personData['profile_photo'] = URL::to('/profile-photos/' . $p->profile_photo);
                }
                return $personData;
            }),
        ];

        if ($this->isEp) {
            $ep = $this->expertPanel;
            $data += [
                'expert_panel' => [
                    'uuid' => $ep->uuid,
                    'affilitaion_id' => $ep->affiliation_id,
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
