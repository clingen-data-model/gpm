<?php

namespace App\Modules\Person\Events;

use App\Modules\Person\Models\Attestation;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class AttestationCompleted extends PersonEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Person $person, public Attestation $attestation)
    {
        $this->person = $person;
        $this->attestation = $attestation;
    }

    public function getLogEntry(): string
    {
        return 'Core Member attestation completed by ' . $this->person->first_name . ' ' . $this->person->last_name . '.';
    }

    public function getProperties(): array
    {
        return [
            'attestation_uuid' => $this->attestation->uuid ?? null,
            'experience_type'  => $this->attestation->experience_type,
            'version'          => $this->attestation->attestation_version,
            'attested_by'      => $this->attestation->attested_by
        ];
    }
}
