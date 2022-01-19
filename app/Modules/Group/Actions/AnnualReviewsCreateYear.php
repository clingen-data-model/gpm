<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\AnnualReviewWindow;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class AnnualReviewsCreateYear
{
    use AsCommand;

    public $commandSignature = 'annual-reviews:init-window';

    public function __construct(private AnnualReviewCreate $reviewCreate)
    {
    }
    

    public function handle($forYear, $start, $end)
    {
        $window = new AnnualReviewWindow([
            'for_year' => $forYear,
            'start' => $start,
            'end' => $end
        ]);
        
        $window->save();

        $annualReviews = ExpertPanel::all()->map(function ($ep) {
            return $this->reviewCreate->handle($ep);
        });

        return [
            $window,
            $annualReviews
        ];
    }

    public function asCommand(Command $command)
    {
        $forYear = $command->ask('What year does the window cover?', (Carbon::now()->year-1));
        $start = $command->ask('When does the review window begin?');
        $end = $command->ask('When does the review window end?');

        [$window, $annualReviews] = $this->handle($forYear, $start, $end);
        $command->info('The annual review window is scheduled for '.$start.' to '.$end.'.');
        $command->info('Annual reviews created for '.$annualReviews->count().' expert panels.');
    }
}
