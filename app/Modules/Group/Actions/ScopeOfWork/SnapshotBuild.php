<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

class SnapshotBuild
{
    use AsObject;

    public function handle(Group $group): array
    {
        $group->loadMissing([
            'type',
            'status',
            'expertPanel',
            'expertPanel.genes',
            'members',
            'members.person',
            'members.person.institution',
            'members.person.credentials',
            'members.person.expertises',
            'members.roles',
        ]);

        return [
            'schema_version' => '1.0.0',
            'created_at' => now()->toISOString(),

            'group' => $this->groupPayload($group),

            'expert_panel' => $this->expertPanelPayload($group),

            'scope_of_work' => [
                'panel_name' => $group->name,
                'scope_description' => $group->expertPanel?->scope_description,
                'membership_description' => $group->expertPanel?->membership_description,                
                'scope_genes' => $this->scopeGenesPayload($group),
                'members' => $this->membersPayload($group),
            ],
            
            // Placeholder for future supporting data that may be relevant to scope of work changes.
            'supporting_data' => [
                'documents' => [],
                'publications' => [],
                'funding_awards' => [],
            ],
        ];
    }

    private function groupPayload(Group $group): array
    {
        return [
            'id' => $group->id,
            'uuid' => $group->uuid,
            'name' => $group->name,
            'type' => $group->type?->name,
            'type_id' => $group->group_type_id,
            'status' => $group->status?->name,
            'status_id' => $group->group_status_id,
            'parent_id' => $group->parent_id,
            'created_at' => optional($group->created_at)->toISOString(),
            'updated_at' => optional($group->updated_at)->toISOString(),
        ];
    }

    private function expertPanelPayload(Group $group): ?array
    {
        $expertPanel = $group->expertPanel;

        if (!$expertPanel) {
            return null;
        }

        return [
            'id' => $expertPanel->id,
            'uuid' => $expertPanel->uuid,
            'affiliation_id' => $expertPanel->affiliation_id,
            'expert_panel_type_id' => $expertPanel->expert_panel_type_id,
            'current_step' => $expertPanel->current_step,

            'long_base_name' => $expertPanel->long_base_name,
            'short_base_name' => $expertPanel->short_base_name,

            'scope_description' => $expertPanel->scope_description,
            'membership_description' => $expertPanel->membership_description,

            'date_initiated' => optional($expertPanel->date_initiated)->toISOString(),
            'date_completed' => optional($expertPanel->date_completed)->toISOString(),

            'step_1_received_date' => optional($expertPanel->step_1_received_date)->toISOString(),
            'step_1_approval_date' => optional($expertPanel->step_1_approval_date)->toISOString(),
            'step_2_approval_date' => optional($expertPanel->step_2_approval_date)->toISOString(),
            'step_3_approval_date' => optional($expertPanel->step_3_approval_date)->toISOString(),
            'step_4_received_date' => optional($expertPanel->step_4_received_date)->toISOString(),
            'step_4_approval_date' => optional($expertPanel->step_4_approval_date)->toISOString(),

            'updated_at' => optional($expertPanel->updated_at)->toISOString(),
        ];
    }

    private function membersPayload(Group $group): array
    {
        return $group->members
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'person_id' => $member->person_id,
                    'person_uuid' => $member->person?->uuid,

                    'first_name' => $member->person?->first_name,
                    'last_name' => $member->person?->last_name,
                    'email' => $member->person?->email,

                    'institution' => $member->person?->institution?->name,
                    'credentials' => $this->credentialsPayload($member->person?->credentials),
                    'expertises' => $this->expertisesPayload($member->person?->expertises),

                    'roles' => $member->roles
                        ->map(fn ($role) => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ])
                        ->sortBy('name')
                        ->values()
                        ->all(),

                    'notes' => $member->notes,
                    'training_level_1' => (bool) $member->training_level_1,
                    'training_level_2' => (bool) $member->training_level_2,

                    'expertise' => $member->expertise,
                    'is_contact' => (bool) $member->is_contact,
                    'start_date' => optional($member->start_date)->toISOString(),
                    'end_date' => optional($member->end_date)->toISOString(),
                    'deleted_at' => optional($member->deleted_at)->toISOString(),                    
                ];
            })
            ->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc'],
                ['person_id', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function credentialsPayload($credentials): array
    {
        if (!$credentials) {
            return [];
        }

        if ($credentials instanceof Collection) {
            return $credentials
                ->map(fn ($credential) => $credential->name ?? $credential->abbreviation ?? null)
                ->filter()
                ->values()
                ->all();
        }

        if (is_string($credentials)) {
            return [$credentials];
        }

        return [];
    }

    private function expertisesPayload($expertises): array
    {
        if (!$expertises instanceof Collection) {
            return [];
        }

        return $expertises
            ->map(fn ($expertise) => $expertise->name ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function scopeGenesPayload(Group $group): array
    {
        $genes = $group->expertPanel?->genes ?? collect();

        return $genes
            ->map(fn ($gene) => [
                'id' => $gene->id,
                'hgnc_id' => $gene->hgnc_id,
                'gene_symbol' => $gene->gene_symbol,
                'mondo_id' => $gene->mondo_id,
                'disease_name' => $gene->disease_name,
                'disease_entity' => $gene->disease_entity ?? null,
                'moi' => $gene->moi,
                'tier' => $gene->tier,
                'date_approved' => $gene->date_approved ?? null,
                'gt_curation_uuid' => $gene->gt_curation_uuid ?? null,
                'plan' => $gene->plan ?? null,
                'created_at' => optional($gene->created_at)->toISOString(),
                'updated_at' => optional($gene->updated_at)->toISOString(),
                'deleted_at' => optional($gene->deleted_at)->toISOString(),
            ])
            ->sortBy([
                ['gene_symbol', 'asc'],
                ['mondo_id', 'asc'],
                ['moi', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->all();
    }
}