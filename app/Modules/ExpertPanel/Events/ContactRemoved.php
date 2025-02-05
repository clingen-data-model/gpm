<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ContactRemoved extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public Person $person)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Removed contact ' . $this->person->name;
    }

    public function getProperties(): array
    {
        return [
            'person' => $this->person->toArray()
        ];
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
