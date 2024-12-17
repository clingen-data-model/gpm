<?php

namespace App\Modules\Group\Events\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

trait IsPublishableApplicationEvent
{
    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getEventType(): string
    {
        return Str::snake(self::class);
    }

    public function getPublishableMessage(): array
    {
        return [
            'expert_panel' => array_merge(
                [
                    'id' => $this->group->uuid,
                    'name' => $this->group->name,
                    'short_name' => $this->group->expertPanel->short_base_name,
                    'status' => $this->group->groupStatus->name,
                    'parent_group' => $this->group->parentGroup?->name, // TODO: check representation when no parent: null or empty string?
                    'type' => $this->group->fullType->name,
                    'affiliation_id' => $this->group->expertPanel->affiliation_id,
                    'scope_description' => $this->group->expertPanel->scope_description,
                    // TODO: consider adding membership_description
                ],
                // Conditionally add vcep field below if type is 'vcep'
                $this->group->fullType->name === 'vcep' ? ['cspec_url' => $this->group->expertPanel->affiliation_id] : [],
            )
        ];
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

    public function mapMemberForMessage($member): array
    {
        return [
            'id' => $member->person->uuid,
            'first_name' => $member->person->first_name,
            'last_name' => $member->person->last_name,
            'email' => $member->person->email,
            'group_roles' => $member->roles->pluck('name')->toArray(),
            'additional_permissions' => $member->permissions->pluck('name')->toArray(),
        ];
    }
}
