<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;

class GroupExternalResource extends JsonResource
{
    use IsPublishableApplicationEvent;

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
    public function toArray($request, $withMembers = true, $withGenes = true)
    {
        $data = [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'caption' => null, // TODO here as a placeholder for now, see GPM-513
            'status' => $this->groupStatus->name,
            'status_date' => $this->groupStatus->updated_at,
            'type' => $this->type->name,
            'coi' => url('/coi-group/'.$this->uuid),
        ];

        if ($withMembers) {
            $data['members'] = $this->members()->with(['person', 'person.credentials', 'person.institution', 'roles', 'latestCoi'])->get()->map(function ($member) { return $this->mapMemberForMessage($member, false); })->toArray();
        }

        if ($this->isEp) {
            $ep = $this->expertPanel;
            $epData = [
                'uuid' => $ep->uuid,
                'affiliation_id' => $ep->affiliation_id,
                'name' => $ep->long_base_name,
                'short_name' => $ep->short_base_name,
                'scope_description' => $ep->scope_description,
                'membership_description' => $ep->membership_description,
                'type' => $ep->type->name,

                'date_completed' => $ep->date_completed,
                'inactive_date' => $this->group_status_id === 5 ? $this->deriveInactiveDate($this->id) : null,
                'current_step' => $ep->current_step,
            ];
            if ($withGenes) {
                $epData['all_genes'] = $ep->genes->map(function ($gene) { return $this->mapGeneForMessage($gene); })->toArray();
            }

            if ($this->isVcep || $this->isScvcep) {
                $epData['vcep_define_group']         = $ep->step_1_approval_date;
                $epData['vcep_classification_rules'] = $ep->step_2_approval_date;
                $epData['vcep_pilot_rules']          = $ep->step_3_approval_date;
                $epData['vcep_approval']             = $ep->step_4_approval_date;
            }
            if ($this->isGcep) {
                $epData['gcep_define_group']         = $ep->step_1_received_date;
                $epData['gcep_approval']             = $ep->step_4_approval_date;
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
