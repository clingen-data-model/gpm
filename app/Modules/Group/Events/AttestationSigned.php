<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\{
    Facades\Auth,
    Carbon
};

class AttestationSigned extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public String $attestationName, public ?Carbon $signedAt)
    {
    }

    public function getLogEntry(): string
    {
        $submitterName = Auth::user() ? Auth::user()->name : 'system';
        if ($this->signedAt) {
            return $this->attestationName.' attestation submitted by '.$submitterName.' on '.$this->signedAt->format('Y-m-d').' at '.$this->signedAt->format('H:i:s').'.';
        }

        return $this->attestationName.' attestation signature removed by '.$submitterName.' on '.Carbon::now()->format('Y-m-d').'.';
    }

    public function getProperties(): ?array
    {
        return [
            'attestation_name' => $this->attestationName,
            'date_submitted' => $this->signedAt
        ];
    }

    public function getLogDate(): Carbon
    {
        $logDate = $this->signedAt ?? Carbon::now();
        return $logDate;
    }

    public function getActivityType(): string
    {
        return strtolower($this->attestationName).'-attestation-submitted';
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}
