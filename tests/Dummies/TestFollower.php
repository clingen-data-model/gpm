<?php

namespace Tests\Dummies;

class TestFollower
{
    public function asFollowAction(TestEvent $event, $expectedName): bool
    {
        if ($event->argument == $expectedName) {
            return true;
        }

        return false;
    }
}