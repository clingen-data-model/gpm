<?php

namespace App\Modules\Group\Events\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use App\Services\CocService;

trait IsPublishableGroupEvent
{
    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function mapGeneForMessage($gene): array
    {
        $messageGene = [
            'hgnc_id' => $gene->hgnc_id,
            'gene_symbol' => $gene->gene_symbol,
        ];

        if ($gene->mondo_id) {
            $messageGene['mondo_id'] = $gene->mondo_id;
            $messageGene['disease_name'] = $gene->disease_name;
            $messageGene['disease_entity'] = $gene->disease_entity;
        }

        return $messageGene;
    }

    public function mapMemberForMessage($member, $withEmail = false): array
    {
        $person = $member->person;
        $roles = $member->roles->pluck('display_name')->toArray();

        $coc = null;
        if ($person && $person->relationLoaded('latestCocAttestation')) {
            $cocService = app(CocService::class);
            $coc = $cocService->statusFor($person);
        }

        $data = [
            'uuid' => $person->uuid,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'roles' => $roles,
            'additional_permissions' => $member->permissions->pluck('name')->toArray(),
            'institution' => $person->institution->name ?? null,
            'credentials' => $person->credentials->map(function ($credential) {
                                return $credential->name;
                            })->toArray(),
            'code_of_conduct' => $coc,
        ];
        if($person->profile_photo) { $data['profile_photo'] = URL::to('/profile-photos/' . $person->profile_photo); }
        if(array_intersect($roles, ['Coordinator', 'Chair']) || $withEmail) { $data['email'] = $person->email; }
        return $data;
    }

    public function mapGroupForMessage($withMembers = true, $withGenes = true)
    {
        $group = $this->group;
        $data = [
            'uuid' => $group->uuid,
            'name' => $group->name,
            'description' => $group->description,
            'caption' => $group->caption,
            'icon_url' => $group->icon_url_raw,
            'status' => $group->groupStatus->name,
            'status_date' => $group->groupStatus->updated_at->toISO8601String(),
            'type' => $group->type->name,
            'coi' => url('/coi-group/'.$group->uuid),
        ];

        if ($withMembers) {
            $data['members'] = $group->members()
                ->isActive()
                ->with(['person', 'person.credentials', 'person.institution', 'roles', 'permissions', 'latestCoi', 'person.latestCocAttestation'])
                ->get()
                ->map(function ($member) {
                    return $this->mapMemberForMessage($member, false);
                })->toArray();
        }

        if ($group->isEp) {
            $ep = $group->expertPanel;
            $epData = [
                'uuid' => $ep->uuid,
                'affiliation_id' => $ep->affiliation_id,
                'name' => $ep->long_base_name,
                'short_name' => $ep->short_base_name,
                'scope_description' => $ep->scope_description,
                'membership_description' => $ep->membership_description,
                'type' => $ep->type->name,

                'date_completed' => $ep->date_completed,
                'inactive_date' => $group->group_status_id === 5 ? $this->deriveInactiveDate($group->id) : null,
                'current_step' => $ep->current_step,
            ];
            if ($withGenes) {
                $epData['all_genes'] = $ep->genes->map(function ($gene) { return $this->mapGeneForMessage($gene); })->toArray();
            }

            if ($group->isVcep || $group->isScvcep) {
                $epData['clinvar_org_id']                       = $ep->clinvar_org_id;
                $epData['vcep_definition_approval']             = $ep->step_1_approval_date;
                $epData['vcep_draft_specification_approval']    = $ep->step_2_approval_date;
                $epData['vcep_pilot_approval']                  = $ep->step_3_approval_date;
                $epData['vcep_final_approval']                  = $ep->step_4_approval_date;
            }
            if ($group->isGcep) {
                $epData['gcep_final_approval']  = $ep->step_1_approval_date;
            }

            $data['expert_panel'] = $epData;
        }

        if ($group->parent) {
            $data += [
                'parent' => [
                    'uuid' => $group->parent->uuid,
                    'name' => $group->parent->name,
                    'type'   => optional($group->parent->type)->name,
                    'status' => optional($group->parent->status)->name,
                ],
            ];
        }

        return $data;
    }

    private function deriveInactiveDate($groupID): ?string
    {
        $subjectType = 'App\\Modules\\Group\\Models\\Group';

        $ts = DB::table('activity_log')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $groupID)
            ->where('description', 'like', '%inactive%')
            ->latest('created_at')
            ->value('created_at');

        return $ts ? Carbon::parse($ts)->toDateString() : null;
    }
}
