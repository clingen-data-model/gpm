<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Support\Carbon;

interface PublishableEvent extends Event
{
    public function getTopic(): string;
    public function getLogDate(): Carbon;
    public function getEventType(): string;
    public function getPublishableMessage(): array;
    public function shouldPublish(): bool;
    public function checkpointIfNeeded(): void;
}
