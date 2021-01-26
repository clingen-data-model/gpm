<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Domain\Application\Models\Application;
use App\Http\Requests\Applications\CreateApplicationLogEntryRequest;

class ApplicationLogController extends Controller
{
    public function store($applicationUuid, CreateApplicationLogEntryRequest $request)
    {
        $application = Application::findByUuidOrFail($applicationUuid);
        $logger = activity('applications');
        $logger->performedOn($application);
        $logger->createdAt(Carbon::parse($request->created_at));
        $logger->causedBy(Auth::user());

        if ($request->step) {
            $logger->withProperties(['step' => $request->step]);
        }

        $logEntry = $logger->log($request->description);

        return response($logEntry->toArray(), 200);
    }
}
