<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use App\Modules\Application\Jobs\AddLogEntry;
use Illuminate\Validation\ValidationException;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Jobs\DeleteLogEntry;
use App\Modules\Application\Jobs\UpdateLogEntry;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Http\Requests\UpdateApplicationLogEntryRequest;
use App\Http\Requests\Applications\CreateApplicationLogEntryRequest;

class ApplicationLogController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function index($applicationUuid, Request $request)
    {
        $application = Application::findByUuidOrFail($applicationUuid);
        return $application->logEntries()->with([
            'causer' => function (MorphTo $morphTo) {
                $morphTo;
            }
        ])->get();
    }
    

    public function store($applicationUuid, CreateApplicationLogEntryRequest $request)
    {
        $job = new AddLogEntry(
            applicationUuid: $applicationUuid, 
            logDate: $request->log_date, 
            entry: $request->entry, 
            step: $request->step
        );
        $this->dispatcher->dispatch($job);

        $logEntry = Application::latestLogEntryForUuid($applicationUuid);
        $logEntry->load(['causer']);
        return $logEntry;
    }

    public function update($applicationUuid, $logEntryId, UpdateApplicationLogEntryRequest $request)
    {
        $job = new UpdateLogEntry(
            applicationUuid: $applicationUuid,
            logEntryId: $logEntryId,
            entry: $request->entry,
            step: $request->step,
            logDate: $request->log_date
        );
        Bus::dispatch($job);

        $logEntry = Application::latestLogEntryForUuid($applicationUuid);
        $logEntry->load(['causer']);
        return $logEntry;
    }

    public function destroy($applicationUuid, $logEntryId)
    {
        try {
            Bus::dispatch(new DeleteLogEntry(
                applicationUuid: $applicationUuid,
                logEntryId: $logEntryId,
            ));

            return response('', 200);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['activity_type' => ['Only manual log entries can be deleted.']]);
        }
    }
    
}
