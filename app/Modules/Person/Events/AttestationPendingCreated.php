<?php

namespace App\Modules\Person\Events;

use App\Modules\Person\Models\Attestation;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class AttestationPendingCreated extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Person $person, public Attestation $attestation)
    {
        $this->person = $person;
        $this->attestation = $attestation;
    }

    public function getLogEntry(): string
    {
        return 'Core Member attestation stub created (pending) for ' . $this->person->first_name . ' ' . $this->person->last_name . '.';
    }

    public function getProperties(): array
    {
        return [
            'attestation_uuid' => $this->attestation->uuid ?? null,
            'status' => 'pending',
        ];
    }

}
