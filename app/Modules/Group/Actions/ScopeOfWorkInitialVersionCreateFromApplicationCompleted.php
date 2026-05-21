<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\Concerns\AsListener;

class ScopeOfWorkInitialVersionCreateFromApplicationCompleted
{
    use AsListener;

    public function handle(ExpertPanel $expertPanel): void
    {
        $expertPanel->loadMissing('group');

        if (!$expertPanel->group) {
            return;
        }

        ScopeOfWorkInitialVersionCreate::run($expertPanel->group);
    }

    public function asListener(ApplicationCompleted $event): void
    {
        $this->handle($event->expertPanel);
    }
}