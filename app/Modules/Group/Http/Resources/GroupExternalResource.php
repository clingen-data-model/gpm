<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\DB;

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
        $d = fn($dt) => optional($dt)->toDateString();

        $data = [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->groupStatus->name,
            'status_date' => $d($this->groupStatus->updated_at),
            'type' => $this->type->name,
            'coi' => url('/coi/'.$this->uuid),
            'members' => $this->members()->with(['person', 'person.credentials', 'roles', 'latestCoi'])->get()->map(function ($member) {
                $p = $member->person;
                $personData = [
                    'name'        => $p->name,
                    'email'       => $p->email, 
                    'credentials' => $p->credentials->map(function ($credential) {
                        return $credential->name;
                    }),
                    'uuid' => $p->uuid,
                    'roles' => $member->roles->map(function ($role) {
                        return $role->display_name;
                    }),
                    // 'last_coi_completion_date' => $member->latestCoi?->completed_at,
                ];
                if ($p->profile_photo) {
                    $personData['profile_photo'] = URL::to('/profile-photos/' . $p->profile_photo);
                }
                return $personData;
            }),
        ];

        if ($this->isEp) {
            $ep = $this->expertPanel;
            $epData = [
                'uuid' => $ep->uuid,
                'affiliation_id' => $ep->affiliation_id,
                'name' => $ep->long_base_name,
                'short_name' => $ep->short_base_name,
                // 'scope_description' => $ep->scope_description,
                'membership_description' => $ep->membership_description,
                'type' => $ep->type->name,

                'date_completed' => $d($ep->date_completed),
                'inactive_date' => $this->group_status_id === 5 ? $this->deriveInactiveDate($this->id) : null,
                'current_step' => $ep->current_step,
            ];
            
            if ($this->isVcep || $this->isScvcep) {
                $epData['vcep_define_group']         = $d($ep->step_1_approval_date);
                $epData['vcep_classification_rules'] = $d($ep->step_2_approval_date);
                $epData['vcep_pilot_rules']          = $d($ep->step_3_approval_date);
                $epData['vcep_approval']             = $d($ep->step_4_approval_date);
            }
            if ($this->isGcep) {
                $epData['gcep_define_group']         = $d($ep->step_1_received_date);
                $epData['gcep_approval']             = $d($ep->step_4_approval_date);
            }
            
            $data['expert_panel'] = $epData;
        }

        if ($this->parent) {
            $data += [
                'parent' => [
                    'uuid' => $this->parent->uuid,
                    'name' => $this->parent->name,
                    'type'   => optional($this->parent->type)->name,
                    'status' => optional($this->parent->status)->name,
                ],
            ];
        }
        
        return $data;
    }

    protected function deriveInactiveDate($groupID): ?string
    {
        $subjectType = 'App\\Modules\\Group\\Models\\Group';

        $ts = DB::table('activity_log')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $groupID)
            ->where('description', 'like', '%inactive%')
            ->latest('created_at')
            ->value('created_at');

        return $ts ? \Illuminate\Support\Carbon::parse($ts)->toDateString() : null;
    }
}
