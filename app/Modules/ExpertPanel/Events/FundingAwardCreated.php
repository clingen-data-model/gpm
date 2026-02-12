<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\ExpertPanel\Service\MapsFundingAwardsForDx;

class FundingAwardCreated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use MapsFundingAwardsForDx;

    public function __construct(public ExpertPanel $application, public FundingAward $fundingAward)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {   
        return 'Added funding award ' . $this->fundingAward->fundingSource->name . 
                ' from ' . $this->fundingAward->start_date?->format('Y-m-d') . ' to ' . $this->fundingAward->end_date?->format('Y-m-d') . '.'  ;
    }

    public function getProperties(): array
    {
        return $this->fundingAwardPayload($this->fundingAward);

        return [
                    'uuid' => (string) $this->fundingAward->uuid,
                    'funding_source'    => FundingSourceSchema::forMessage($this->fundingAward->fundingSource),
                    'award_number'      => $this->fundingAward->award_number,
                    'start_date'        => $this->fundingAward->start_date?->format('Y-m-d'),
                    'end_date'          => $this->fundingAward->end_date?->format('Y-m-d'),
                    'award_url'         => $this->fundingAward->award_url,
                    'funding_source_division' => $this->fundingAward->funding_source_division,
                    'rep_contacts'      => $this->fundingAward->rep_contacts ?? [],
                    'notes'             => $this->fundingAward->notes,
                    'contact_pis'       => $this->fundingAward->contactPis->map(function ($contactPI) {
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
