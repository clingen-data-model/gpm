<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AttestationSigned extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
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

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
