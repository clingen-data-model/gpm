<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;

class ApplicationEmailSent extends GroupEvent
{
    public function __construct(public Group $group, public string $subject, public string $body, public ?int $step = null)
    {
    }

    public function getLogEntry(): string
    {
        return 'Email sent to contacts: '.$this->subject."\n".$this->body;
    }

    public function getProperties(): array
    {
        return ['subject' => $this->subject, 'body' => $this->body, 'step' => $this->step];
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}
