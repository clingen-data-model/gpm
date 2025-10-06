<?php
namespace App\Modules\Group\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use App\Modules\Group\Models\Group;
use App\Jobs\EmitGroupCheckpointJob;
use App\Modules\Group\Events\GroupCheckpointEvent;

class EmitGroupCheckpoints
{
    use AsAction;

    public function handle(Collection $groups, bool $queue = true): array
    {
        if ($groups->isEmpty()) {
            return ['accepted' => 0, 'ids' => []];
        }

        if ($queue) {
            foreach ($groups as $g) {
                EmitGroupCheckpointJob::dispatch($g->id);
            }
        } else {
            foreach ($groups as $g) {
                event(new GroupCheckpointEvent($g));
            }
        }
        return ['accepted' => $groups->count(), 'ids' => $groups->pluck('id')->all()];
    }

    public function asController(Request $request)
    {
        $raw = $request->input('group_ids');
        $ids = is_null($raw) ? null : collect(is_array($raw) ? $raw : preg_split('/[\s,]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY))->map(fn ($v) => (int)$v)->filter(fn ($v) => $v > 0)->unique()->values();

        $bulkAll = is_null($ids) || $ids->isEmpty();

        $candidates = $bulkAll ? Group::query()->get() : Group::whereIn('id', $ids->all())->get();

        if ($bulkAll && Gate::denies('checkpoint', Group::class)) {
            abort(403, 'You are not allowed to checkpoint all groups.');
        }

        $allowed   = $candidates->filter(fn (Group $g) => Gate::allows('checkpoint', $g))->values();
        $deniedIds = $candidates->diff($allowed)->pluck('id')->all();

        $notFoundIds = [];
        if (!$bulkAll) {
            $found = $candidates->pluck('id')->all();
            $notFoundIds = array_values(array_diff($ids->all(), $found));
        }

        if ($allowed->isEmpty()) {
            abort(403, 'You are not allowed to checkpoint the requested groups.');
        }

        if ($request->boolean('dry_run')) {
            return response()->json([
                'dry_run'      => true,
                'would_emit'   => $allowed->count(),
                'denied_ids'   => $deniedIds,
                'not_found_ids'=> $notFoundIds,
            ]);
        }

        $res = $this->handle($allowed, $request->boolean('queue', true));

        return response()->json([
            'status'        => $request->boolean('queue', true) ? 'queued' : 'emitted',
            'accepted'      => $res['accepted'],
            'ids'           => $res['ids'],
            'denied_ids'    => $deniedIds,
            'not_found_ids' => $notFoundIds,
        ], $request->boolean('queue', true) ? 202 : 200);
    }
}
