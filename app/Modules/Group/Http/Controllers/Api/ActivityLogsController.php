<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Models\LogEntry;
use App\Actions\LogEntryAdd;
use Illuminate\Http\Request;
use InvalidArgumentException;
use App\Actions\LogEntryDelete;
use App\Actions\LogEntryUpdate;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\LogEntryResource;
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

    public function index(Request $request, $groupUuid)
    {
        $group = Group::where('uuid', $groupUuid)->sole();

        //Extract to policy the next time this is an issue
        if (Auth::user()->cannot('viewGroupLogs', $group)) {
            throw new AuthorizationException('You do not have access to view this groups activity logs.');
        }

        $query = $group->logEntries()->select([
                            'id',
                            'activity_type',
                            'description',
                            'causer_id',
                            'causer_type',
                            'created_at',
                            'subject_id',
                            'properties',
                        ])
                        ->with(['causer' => function ($q) {
                            return $q->select(['id', 'name']);
                        }])
                        ->where(function($q) {
                            $q->whereNotIn('activity_type', ['coi-completed','next-action-updated'])
                            ->orWhereNull('activity_type');
                        })
                        ->orderBy('created_at', 'desc');

        $allLogs = $query->get();

        $customLogs = $allLogs->filter(function ($entry) {
            return $entry->activity_type == null;
        });

        $autoLogs = $allLogs->filter(function ($entry) {
            return $entry->activity_type !== null;
        })
        ->unique(function ($i) {
            return $i->activity_type.'-'.$i->created_at->format('Y-m-d_H:i');
        });

        $logEntries = $autoLogs->merge($customLogs)->sortByDesc('created_at');

        return LogEntryResource::collection($logEntries);
    }

    public function store(CreateLogEntryRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->cannot('create', LogEntry::class)) {
            throw new AuthorizationException('You do not have access to add log entries.');
        }

        $logEntry = $this->addEntry->handle(
            subject: $group,
            logDate: $request->log_date,
            entry: $request->entry,
            step: $request->step
        );

        return $logEntry;
    }

    public function update(UpdateLogEntryRequest $request, $groupUuid, $logEntryId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $logEntry = $group->logEntries()->findOrFail($logEntryId);

        if (Auth::user()->cannot('update', $logEntry)) {
            throw new AuthorizationException('You do not have access to update activity logs.');
        }


        $logEntry = $this->updateEntry->handle(
            subject: $group,
            logEntry: $logEntry,
            entry: $request->entry,
            step: $request->step,
            logDate: $request->log_date
        );

        $logEntry->load(['causer']);
        return $logEntry;
    }

    public function destroy($groupUuid, $logEntryId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $logEntry = $group->logEntries()->findOrFail($logEntryId);

        if (Auth::user()->cannot('delete', $logEntry)) {
            throw new AuthorizationException('You do not have access to update activity logs.');
        }

        try {
            $this->deleteEntry->handle(
                logEntry: $logEntry,
            );

            return response('', 200);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['activity_type' => ['Only manual log entries can be deleted.']]);
        }
    }
}
