<?php

namespace App\Actions;

use App\Models\Activity;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class LogEntryDelete
{
    use AsAction;

    /**
     * Create a new job instance.
     */
    public function handle(Activity $logEntry): void
    {
        if (! is_null($logEntry->activity_type)) {
            throw new InvalidArgumentException('Only manual log entries can be deleted.');
        }

        $logEntry->delete();
    }
}
