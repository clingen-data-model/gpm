<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Validation\ValidationException;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\LogEntryAdd;
use App\Modules\ExpertPanel\Jobs\DeleteLogEntry;
use App\Modules\ExpertPanel\Jobs\UpdateLogEntry;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Modules\ExpertPanel\Http\Requests\CreateApplicationLogEntryRequest;
use App\Modules\ExpertPanel\Http\Requests\UpdateApplicationLogEntryRequest;

class ApplicationLogController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function index($expertPanelUuid, Request $request)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        return $expertPanel->logEntries()->with([
            'causer' => function (MorphTo $morphTo) {
                $morphTo;
            }
        ])->get();
    }
    

    public function store($expertPanelUuid, CreateApplicationLogEntryRequest $request)
    {
        $job = (new LogEntryAdd)->handle(
            expertPanelUuid: $expertPanelUuid,
            logDate: $request->log_date,
            entry: $request->entry,
            step: $request->step
        );

        $logEntry = ExpertPanel::latestLogEntryForUuid($expertPanelUuid);
        $logEntry->load(['causer']);
        return $logEntry;
    }

    public function update($expertPanelUuid, $logEntryId, UpdateApplicationLogEntryRequest $request)
    {
        $job = new UpdateLogEntry(
            expertPanelUuid: $expertPanelUuid,
            logEntryId: $logEntryId,
            entry: $request->entry,
            step: $request->step,
            logDate: $request->log_date
        );
        Bus::dispatch($job);

        $logEntry = ExpertPanel::latestLogEntryForUuid($expertPanelUuid);
        $logEntry->load(['causer']);
        return $logEntry;
    }

    public function destroy($expertPanelUuid, $logEntryId)
    {
        try {
            Bus::dispatch(new DeleteLogEntry(
                expertPanelUuid: $expertPanelUuid,
                logEntryId: $logEntryId,
            ));

            return response('', 200);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['activity_type' => ['Only manual log entries can be deleted.']]);
        }
    }
}
