<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\FundingAward;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class FundingAwardDeleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public array $fundingAward)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {   
        return 'Deleted funding award ' . $this->fundingAward['name'] . '.'  ;
    }

    public function getProperties(): array
    {
        
        return [ 'uuid' => (string) $this->fundingAward['uuid'] ];
    }

}
