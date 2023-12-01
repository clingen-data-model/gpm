<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Actions\LogEntryDelete as AppLogEntryDelete;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;

class LogEntryDelete
{
    use AsAction;

    public function __construct(private AppLogEntryDelete $logEntryDelete)
    {
        //code
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(string $expertPanelUuid, int $logEntryId)
    {
        $logEntry = ExpertPanel::findByUuidOrFail($expertPanelUuid)
                        ->group
                        ->logEntries()
                        ->findOrFail($logEntryId);

        $this->logEntryDelete->handle(logEntry: $logEntry);
    }

    public function asController(string $expertPanelUuid, int $logEntryId)
    {
        try {
            $this->handle(
                expertPanelUuid: $expertPanelUuid,
                logEntryId: $logEntryId,
            );

            return response('', 200);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['activity_type' => ['Only manual log entries can be deleted.']]);
        }
    }
}
