<?php

namespace App\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Http\Requests\CreateApplicationLogEntryRequest;

class LogEntryAdd
{
    use AsAction;

    private ExpertPanel  $expertPanel;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        Model $subject,
        string $logDate,
        string $entry,
        ?int $step = null
    ) {
        $logEntry = activity('applications')
            ->performedOn($subject)
            ->createdAt(Carbon::parse($logDate))
            ->causedBy(Auth::user())
            ->withProperties([
                'entry' => $entry,
                'log_date' => $logDate,
                'step' => $step
            ])->log($entry);
        $subject->touch();

        return $logEntry;
    }
}
