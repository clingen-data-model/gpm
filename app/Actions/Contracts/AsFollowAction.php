<?php

namespace App\Actions\Contracts;

use App\Events\Event;

/**
 * Adds 
 */
interface AsFollowAction
{
    public function asFollowAction(Event $event, ...$args);
}
