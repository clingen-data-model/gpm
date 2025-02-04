<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ExpertPanelDeleted;
use Lorisleiva\Actions\Concerns\AsAction;

class ExpertPanelDelete
{
    use AsAction;

    public function handle(String $expertPanelUuid)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $expertPanel->delete();

        event(new ExpertPanelDeleted(expertPanel: $expertPanel));
    }
}
