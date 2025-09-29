<?php 

namespace App\Modules\ExpertPanel\Listeners;

use App\Modules\ExpertPanel\Actions\SyncGtMembersAction;
use App\Modules\ExpertPanel\Events\StepApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class RegisterMembersInGtOnGcepApproval implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;
    public $tries = 3;
    public $backoff = [10, 60, 300];

    public function __construct(private SyncGtMembersAction $action) {}

    public function middleware(StepApproved $e): array
    {
        return [ new WithoutOverlapping("gt-membersync:{$e->application->uuid}:step:{$e->step}") ];
    }

    public function handle(StepApproved $event): void
    {
        $ep   = $event->application;
        $step = (int) $event->step;

        if (! $ep->is_gcep || $step !== 1) return;

        $this->action->handle($ep, 'add');
    }
}
