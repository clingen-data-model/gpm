<?php

namespace App\Modules\Funding\Events;

use App\Events\PublishableEvent;
use App\Events\RecordableEvent;
use App\Modules\Funding\Models\FundingSource;
use App\Modules\Funding\Support\FundingSourceSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class FundingSourceEvent extends RecordableEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FundingSource $fundingSource)
    {
        $this->fundingSource->loadMissing('fundingType');
    }

    public function hasSubject(): bool
    {
        return true;
    }

    public function getSubject(): Model
    {
        return $this->fundingSource;
    }

    public function getLog(): string
    {
        return 'funding_sources';
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    abstract public function getLogEntry(): string;

    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-general-events');
    }

    public function shouldPublish(): bool
    {
        return true;
    }

    public function getEventType(): string
    {
        return Str::snake((new \ReflectionClass($this))->getShortName());
    }

    public function getProperties(): ?array
    {
        return FundingSourceSchema::forMessage($this->fundingSource);
    }

    public function getPublishableMessage(): array
    {
        return $this->getProperties() ?? [];
    }
}
