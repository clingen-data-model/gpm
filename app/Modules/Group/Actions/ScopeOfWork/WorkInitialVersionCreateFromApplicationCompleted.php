<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\Concerns\AsListener;

class InitialVersionCreateFromApplicationCompleted
{
    use AsListener;

    public function handle(ExpertPanel $expertPanel): void
    {
        $expertPanel->loadMissing('group');

        if (!$expertPanel->group) {
            return;
        }

        InitialVersionCreate::run($expertPanel->group);
    }

    public function asListener(ApplicationCompleted $event): void
    {
        $this->handle($event->expertPanel);
    }
}