<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\LogEntry;
use App\ModelSearchService;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class LogEntrySearch
{
    use AsController;

    public function handle(ActionRequest $request)
    {
        $searcher = new ModelSearchService(
            modelClass: Activity::class,
        );

        return $searcher->search($request->only(['with', 'where', 'sort']));
    }
}
