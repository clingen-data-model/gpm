<?php

namespace App\Modules\ExpertPanel\Service;

use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\Funding\Support\FundingSourceSchema;

final class FundingAwardSchema
{
    public static function forMessage(FundingAward $award): array
    {
        $award->loadMissing(['fundingSource.fundingType', 'contactPis']);
        return [
            'uuid' => (string) $award->uuid,
            'funding_source' => $award->fundingSource ? FundingSourceSchema::forMessage($award->fundingSource) : null,
            'award_number' => $award->award_number,
            'start_date'   => $award->start_date?->format('Y-m-d'),
            'end_date'     => $award->end_date?->format('Y-m-d'),
            'award_url'    => $award->award_url,
            'funding_source_division' => $award->funding_source_division,
            'rep_contacts' => $award->rep_contacts ?? [],
            'notes'        => $award->notes,
            'contact_pis'  => $award->contactPis->map(fn ($p) => [
                'uuid'       => (string) $p->uuid,
                'first_name' => $p->first_name,
                'last_name'  => $p->last_name,
                'email'      => $p->email,
                'is_primary' => (bool) ($p->pivot?->is_primary ?? false),
            ])->values()->all(),
        ];
    }
}