<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundingAwardCreated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public FundingAward $fundingAward)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {   
        $sourceNames = $this->fundingAward->fundingSources->pluck('name')->join(', ');
        return 'Added funding award ' . $sourceNames . ' from ' . $this->fundingAward->start_date?->format('Y-m-d') . ' to ' . $this->fundingAward->end_date?->format('Y-m-d') . '.'  ;
    }

    public function getProperties(): array
    {
        return $this->fundingAward->toExchangePayload();
    }

}
