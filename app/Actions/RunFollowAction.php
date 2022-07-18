<?php

namespace App\Actions;

use Carbon\Carbon;
use App\Events\Event;
use App\Models\FollowAction;
use Lorisleiva\Actions\Concerns\AsListener;

class RunFollowAction
{
    use AsListener;

    public function handle(FollowAction $followAction, Event $event)
    {
        // $followerClass = $followAction->follower;
        $follower = app()->make($followAction->follower);

        $args = $followAction->args ?? [];
        if ($follower->asFollowAction($event, ...$args)) {
            $followAction->update(['completed_at' => Carbon::now()]);
        }
    }

    public function asListener(Event $event)
    {
        $followActions = FollowAction::query()
                            ->forEvent(get_class($event))
                            ->incomplete()
                            ->get();

        $followActions->each(fn ($fa) => $this->handle($fa, $event));
    }
}