<?php

namespace App\Actions;

use App\Events\Event;
use App\Models\FollowAction;
use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsListener;

class FollowActionRun
{
    use AsListener;

    public function handle(FollowAction $followAction, Event $event)
    {
        $follower = app()->make($followAction->follower);

        $args = $followAction->args ?? [];
        if ($follower->asFollowAction($event, ...$args)) {
            $followAction->update(['completed_at' => Carbon::now()]);
        }
    }

    public function asListener(Event $event)
    {
        $followActions = FollowAction::query()
            ->forEvent($event::class)
            ->incomplete()
            ->get();

        $followActions->each(fn ($fa) => $this->handle($fa, $event));
    }
}
