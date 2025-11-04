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
use Illuminate\Support\Facades\DB;

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
        $this->authorize('viewGroupLogs', $group);
        $base = $group->logEntries()
            ->where(function ($q) {
                $q->whereNotIn('activity_type', ['coi-completed','next-action-updated'])
                ->orWhereNull('activity_type');
            });
        $modelClass = get_class($group->logEntries()->getModel());

        $customLogs = (clone $base)
            ->whereNull('activity_type')
            ->with(['causer:id,name'])
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get();

        $autoInner = (clone $base)
            ->whereNotNull('activity_type')
            ->selectRaw("id, activity_type,
                        TRIM(REGEXP_REPLACE(IFNULL(description,''), '\\s+', ' ')) AS norm_desc,
                        DATE_FORMAT(created_at, '%Y-%m-%d %H') AS minute_key");

        $keepIds = DB::query()->fromSub($autoInner->selectRaw("ROW_NUMBER() OVER (PARTITION BY activity_type, norm_desc, minute_key ORDER BY id DESC) AS rn"),'x')
                                ->where('rn', 1)
                                ->select('id')
                                ->pluck('id');

        $autoLogs = $group->logEntries()
                            ->whereIn('id', $keepIds)
                            ->with(['causer:id,name'])
                            ->orderByDesc('created_at')->orderByDesc('id')
                            ->get();

        $logEntries = $autoLogs->merge($customLogs)->sortByDesc(fn ($i) => [$i->created_at, $i->id])->values();

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
