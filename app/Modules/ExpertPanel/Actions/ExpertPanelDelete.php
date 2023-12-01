<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\ExpertPanelDeleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\Concerns\AsAction;

class ExpertPanelDelete
{
    use AsAction;

    public function handle(string $expertPanelUuid)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $expertPanel->delete();

        event(new ExpertPanelDeleted(application: $expertPanel));
    }
}
