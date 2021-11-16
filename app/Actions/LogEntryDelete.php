<?php

namespace App\Actions;

use App\Models\Activity;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class LogEntryDelete
{
    use AsAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(Activity $logEntry)
    {
        if (!is_null($logEntry->activity_type)) {
            throw new InvalidArgumentException('Only manual log entries can be deleted.');
        }

        $logEntry->delete();
    }
}
