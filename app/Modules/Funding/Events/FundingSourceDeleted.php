<?php

namespace App\Modules\Funding\Events;

class FundingSourceDeleted extends FundingSourceEvent
{
    public function getLogEntry(): string
    {
        return "Deleted funding source {$this->fundingSource->name}.";
    }

    public function getProperties(): ?array
    {
        return [ 'uuid' => $this->fundingSource->uuid ];
    }
}
