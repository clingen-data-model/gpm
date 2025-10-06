<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\GroupCheckpointEvent;

class EmitGroupCheckpointJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $groupId) {}

    public function uniqueId(): string
    {
        return 'emit-checkpoint-'.$this->groupId;
    }

    public function middleware(): array
    {
        return [(new ThrottlesExceptions(3, 60))->backoff([30, 60])];
    }

    public function handle(): void
    {
        $group = Group::find($this->groupId);
        if (!$group) { return; }
        event(new GroupCheckpointEvent($group));
    }
}
