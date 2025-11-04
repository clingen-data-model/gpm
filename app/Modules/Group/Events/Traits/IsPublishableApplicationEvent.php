<?php

namespace App\Modules\Group\Events\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

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

    public function mapMemberForMessage($member, $withEmail = true): array
    {
        $person = $member->person;
        $roles = $member->roles->pluck('name')->toArray();
        $data = [
            'uuid' => $person->uuid,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
            'roles' => $roles,
            'institution' => $person->institution->name ?? null,
            'credentials' => $person->credentials->map(function ($credential) {
                                return $credential->name;
                            })->toArray(),            
        ];
        if($person->profile_photo) { $data['profile_photo'] = URL::to('/profile-photos/' . $person->profile_photo); }
        if(array_intersect($roles, ['Coordinator', 'Chair']) || $withEmail) { $data['email'] = $person->email; }
        return $data;
    }
}
