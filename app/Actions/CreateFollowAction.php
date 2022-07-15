<?php

namespace App\Actions;

use App\Models\FollowAction;


class CreateFollowAction
{

    public function handle(string $eventClass, Object $follower)
    {
        return FollowAction::create([
            'event_class' => $eventClass,
            'follower' => $follower
        ]);
    }
}