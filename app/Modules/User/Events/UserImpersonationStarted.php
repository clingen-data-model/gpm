<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;

/**
 * An admin started impersonating $user. Subject is the impersonated user;
 * the causer is the impersonator (who is no longer Auth::user() by the
 * time this is recorded, hence getCauser()).
 */
class UserImpersonationStarted extends UserEvent
{
    public function __construct(public User $user, public User $impersonator)
    {
        parent::__construct($user);
    }

    public function getLogEntry(): string
    {
        return "Impersonation started by {$this->impersonator->name} (#{$this->impersonator->id}).";
    }

    public function getProperties(): ?array
    {
        return [
            'impersonator_id' => $this->impersonator->id,
            'impersonator_name' => $this->impersonator->name,
            'impersonated_id' => $this->user->id,
        ];
    }

    public function getCauser(): ?User
    {
        return $this->impersonator;
    }
}
