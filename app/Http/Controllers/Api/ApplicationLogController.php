<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Domain\Application\Jobs\AddLogEntry;
use App\Domain\Application\Models\Application;
use App\Http\Requests\Applications\CreateApplicationLogEntryRequest;
use Spatie\Activitylog\Models\Activity;

class ApplicationLogController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
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
