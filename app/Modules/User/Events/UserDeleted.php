<?php

namespace App\Modules\User\Events;

use App\Modules\User\Models\User;

class UserDeleted extends UserEvent
{
    public function __construct(public User $user)
    {}

    public function getLogEntry(): string
    {
        return 'User deleted';
    }
    
    
}
