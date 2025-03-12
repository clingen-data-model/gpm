<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ContactAdded extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application, public Person $person)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Added contact ' . $this->person->name . ' to application.';
    }

    public function getProperties(): array
    {
        return [
            'person' => $this->person->toArray()
        ];
    }

}
