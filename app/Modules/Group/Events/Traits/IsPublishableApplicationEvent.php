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
