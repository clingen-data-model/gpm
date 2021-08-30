<?php
namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionCompleted;

class NextActionComplete
{
    use AsAction;

    public function handle(string $expertPanelUuid, string $nextActionUuid, ?string $dateCompleted)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $nextAction = NextAction::findByUuidOrFail($nextActionUuid);

        $nextAction->date_completed = $dateCompleted;
        $nextAction->save();
        $expertPanel->touch();

        Event::dispatch(new NextActionCompleted(application: $expertPanel, nextAction: $nextAction));

        return $nextAction;
    }
}
