<?php

namespace App\Modules\Funding\Events;

class FundingSourceCreated extends FundingSourceEvent
{
    public function getLogEntry(): string
    {
        return "Created funding source {$this->fundingSource->name}.";
    }
}
