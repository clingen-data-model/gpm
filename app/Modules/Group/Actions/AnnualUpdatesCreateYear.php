<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\AnnualUpdateWindow;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class AnnualUpdatesCreateYear
{
    use AsCommand;

    public $commandSignature = 'annual-updates:init-window';

    public function __construct(private AnnualUpdateCreate $updateCreate)
    {
    }
    

    public function handle($forYear, $start, $end)
    {
        $window = new AnnualUpdateWindow([
            'for_year' => $forYear,
            'start' => $start,
            'end' => $end
        ]);
        
        $window->save();

        $annualupdates = ExpertPanel::definitionApproved()
            ->get()
            ->map(function ($ep) use ($window) {
                return $this->updateCreate->handle($ep, $window);
            });

        return [
            $window,
            $annualupdates
        ];
    }

    public function asCommand(Command $command)
    {
        $forYear = $command->ask('What year does the window cover?', (Carbon::now()->year-1));
        $start = $command->ask('When does the update window begin?');
        $end = $command->ask('When does the update window end?');

        [$window, $annualupdates] = $this->handle($forYear, $start, $end);
        $command->info('The annual update window is scheduled for '.$start.' to '.$end.'.');
        $command->info('Annual updates created for '.$annualupdates->count().' expert panels.');
    }
}
