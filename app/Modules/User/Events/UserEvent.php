<?php

namespace App\Modules\User\Events;

use App\Events\RecordableEvent;
use App\Modules\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

abstract class UserEvent extends RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public User $user)
    {
        //
    }

    public function hasSubject(): bool
    {
        return true;
    }

    public function getDescription(): string
    {
        return 'User Created';
    }

    public function getSubject(): Model
    {
        return $this->user;
    }

    public function getProperties(): ?array
    {
        return $this->user->getAttributes();
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getLog(): string
    {
        return 'users';
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
