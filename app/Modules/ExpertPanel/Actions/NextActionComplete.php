<?php
namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\NextAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\NextActionCompleted;

class NextActionComplete
{
    use AsAction;

    public function handle(ExpertPanel $expertPanel, NextAction $nextAction, ?string $dateCompleted)
    {
        $nextAction->date_completed = $dateCompleted;
        $nextAction->save();
        $expertPanel->touch();

        Event::dispatch(new NextActionCompleted(application: $expertPanel, nextAction: $nextAction));

        return $nextAction;
    }

    public function asController(ActionRequest $request, ExpertPanel $expertPanel, NextAction $nextAction)
    {
        return $this->handle($expertPanel, $nextAction, $request->date_completed);
    }

    public function rules(): array
    {
        return [
            'date_completed' => 'required|date'
        ];
    }
    
}
