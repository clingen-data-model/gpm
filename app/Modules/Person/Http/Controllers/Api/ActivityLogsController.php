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

        $user = $person->user()->first();
        $userLogs = $user ? $user->logEntries()->with('causer')->get() : collect();
        $logEntries = $personLogs->merge($userLogs);
        
        return ['data' => $logEntries];
    }
}
