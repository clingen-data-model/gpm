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
    }

}
