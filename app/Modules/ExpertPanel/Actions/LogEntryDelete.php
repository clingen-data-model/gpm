<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\Activity;
use InvalidArgumentException;
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
    public function handle(string $expertPanelUuid, Int $logEntryId)
    {
        $logEntry = ExpertPanel::findByUuidOrFail($expertPanelUuid)
                        ->logEntries()
                        ->findOrFail($logEntryId);

        if (!is_null($logEntry->activity_type)) {
            throw new InvalidArgumentException('Only manual log entries can be deleted.');
        }

        $logEntry->delete();
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
