<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use App\Modules\Application\Jobs\AddLogEntry;
use App\Modules\Application\Models\Application;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
}
