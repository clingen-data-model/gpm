<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Actions\LogEntryAdd;
use Illuminate\Http\Request;
use InvalidArgumentException;
use App\Actions\LogEntryDelete;
use App\Actions\LogEntryUpdate;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use App\Http\Requests\CreateLogEntryRequest;
use App\Http\Requests\UpdateLogEntryRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActivityLogsController extends Controller
{
    public function __construct(
        private LogEntryAdd $addEntry,
        private LogEntryUpdate $updateEntry,
        private LogEntryDelete $deleteEntry
    ) {
    }

    public function index(Request $request, Person $person)
    {
        if (Auth::user()->cannot('viewPersonLogs', $person)) {
            throw new AuthorizationException('You do not have access to view this person\'s activity logs.');
        }
        
        $personLogs = $person->logEntries()->with('causer')->get();

        $user = $person->user()->withTrashed()->first();
        $userLogs = $user ? $user->logEntries()->with('causer')->get() : collect();
        // $userLogs = ($person->user_id) ? $person->user->logEntries()->with('causer')->get() : collect();

        $logEntries = $personLogs->merge($userLogs);
        
        return ['data' => $logEntries];
    }

    // public function store(CreateLogEntryRequest $request, $groupUuid)
    // {
    //     if (!Auth::user()->hasPermissionTo('groups-manage')) {
    //         throw new AuthorizationException('You do not have access to add log entries.');
    //     }

    //     $group = Group::findByUuidOrFail($groupUuid);
    //     $logEntry = $this->addEntry->handle(
    //         subject: $group,
    //         logDate: $request->log_date,
    //         entry: $request->entry,
    //         step: $request->step
    //     );

    //     return $logEntry;
    // }

    // public function update(UpdateLogEntryRequest $request, $groupUuid, $logEntryId)
    // {
    //     if (!Auth::user()->hasPermissionTo('groups-manage')) {
    //         throw new AuthorizationException('You do not have access to update activity logs.');
    //     }

    //     $group = Group::findByUuidOrFail($groupUuid);
    //     $logEntry = $group->logEntries()->findOrFail($logEntryId);

    //     $logEntry = $this->updateEntry->handle(
    //         subject: $group,
    //         logEntry: $logEntry,
    //         entry: $request->entry,
    //         step: $request->step,
    //         logDate: $request->log_date
    //     );

    //     $logEntry->load(['causer']);
    //     return $logEntry;
    // }

    // public function destroy($groupUuid, $logEntryId)
    // {
    //     if (!Auth::user()->hasPermissionTo('groups-manage')) {
    //         throw new AuthorizationException('You do not have access to update activity logs.');
    //     }

    //     $group = Group::findByUuidOrFail($groupUuid);
    //     $logEntry = $group->logEntries()->findOrFail($logEntryId);

    //     try {
    //         $this->deleteEntry->handle(
    //             logEntry: $logEntry,
    //         );

    //         return response('', 200);
    //     } catch (InvalidArgumentException $e) {
    //         throw ValidationException::withMessages(['activity_type' => ['Only manual log entries can be deleted.']]);
    //     }
    // }
}
