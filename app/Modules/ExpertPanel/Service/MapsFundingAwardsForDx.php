<?php

namespace App\Modules\ExpertPanel\Service;

use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\Funding\Models\FundingSource;
use Illuminate\Support\Collection;
use App\Modules\Funding\Support\FundingSourceSchema;

trait MapsFundingAwardsForDx
{
    protected function fundingAwardPayload(FundingAward $fundingAward): array
    {
        return [
            'uuid' => (string) $fundingAward->uuid,
            'funding_source' => FundingSourceSchema::forMessage($this->fundingAward->fundingSource),
            'award_number' => $fundingAward->award_number,
            'start_date'   => $fundingAward->start_date?->format('Y-m-d'),
            'end_date'     => $fundingAward->end_date?->format('Y-m-d'),
            'award_url'               => $fundingAward->award_url,
            'funding_source_division' => $fundingAward->funding_source_division,
            'rep_contacts' => $fundingAward->rep_contacts ?? [],
            'notes' => $fundingAward->notes,
            'contact_pis' => $fundingAward->contactPis->map(function ($contactPI) {
                        return [
                            'uuid'       => (string) $contactPI->uuid,
                            'first_name' => $contactPI->first_name,
                            'last_name'  => $contactPI->last_name,
                            'email'      => $contactPI->email,
                            'is_primary' => (bool) ($contactPI->pivot?->is_primary ?? false),
                        ];
            })->values()->all(),
        ];
    }
}
