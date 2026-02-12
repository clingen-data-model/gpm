<?php

namespace App\Modules\Funding\Events;

class FundingSourceUpdated extends FundingSourceEvent
{
    public function getLogEntry(): string
    {
        return "Updated funding source {$this->fundingSource->name}.";
    }
}
