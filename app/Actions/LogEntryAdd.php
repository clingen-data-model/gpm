<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class LogEntryAdd
{
    use AsAction;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        Model $subject,
        string $logDate,
        string $entry,
        int $step = null
    ): void {
        $logEntry = activity('applications')
            ->performedOn($subject)
            ->createdAt(Carbon::parse($logDate))
            ->causedBy(Auth::user())
            ->withProperties([
                'entry' => $entry,
                'log_date' => $logDate,
                'step' => $step,
            ])->log($entry);
        $subject->touch();

        return $logEntry;
    }
}
