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
            'expert_panel' => [
                'id' => $this->group->uuid,
                'name' => $this->group->displayName,
                'short_name' => $this->group->expertPanel->short_base_name,
                'status' => optional($this->group->groupStatus)->name, // Retrieve the status name
              //  'status' => $this->group->group_Status,
                'parent_group' => optional($this->group->parentGroup)->displayName,
                'type' => $this->group->fullType->name,
                'affiliation_id' => $this->group->expertPanel->affiliation_id
            ],
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
