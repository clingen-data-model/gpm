<?php

namespace App\Actions;

use App\Models\Activity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Http\Requests\UpdateApplicationLogEntryRequest;

class LogEntryUpdate
{
    use AsAction;

    public function handle(
        Model $subject,
        Activity $logEntry,
        string $logDate,
        string $entry,
        ?int $step = null
    ) {
        $logEntry->created_at = Carbon::parse($logDate);
        $logEntry->description = $entry;
        $props = $logEntry->properties ?? collect();
        $props->put('entry', $entry);
        $props->put('step', $step);
        $props->put('log_date', $logDate);
        $logEntry->properties = $props;

        $logEntry->save();

        return $logEntry;
    }
}
