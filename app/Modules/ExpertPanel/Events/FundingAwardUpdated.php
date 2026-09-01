<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundingAwardUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public FundingAward $fundingAward)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {   
        $sourceNames = $this->fundingAward->fundingSources->pluck('name')->join(', ');
        return 'Updated funding award ' . $sourceNames . '.'  ;
    }

    public function getProperties(): array
    {
        return $this->fundingAward->toExchangePayload();
    }

}
