<?php
namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use App\Modules\Group\Events\GeneRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class GeneRemove
{
    use AsController;
    use AsObject;

    public function handle(Group $group, Collection $genes): Group
    {
        DB::transaction(function () use ($group, $genes) {
            foreach ($genes as $gene) {
                $gene->delete();
                event(new GeneRemoved($group, $gene));
            }
        });

        return $group->fresh();
    }

    public function asController(Request $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->cannot('removeGene', $group)) {
            throw new AuthorizationException('You do not have permission to remove genes from this expert panel\'s gene list.');
        }

        if (! $group->isExpertPanel) {
            throw ValidationException::withMessages([
                'group' => ['Only expert panels have genes. You can not remove a gene from a '.$group->type->full_name]]);
        }

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $genes = $group->expertPanel->genes()->whereIn('id', $data['ids'])->get();

        if ($genes->count() !== count($data['ids'])) {
            throw ValidationException::withMessages([
                'ids' => ['One or more selected genes were not found in this expert panel.']
            ]);
        }
        $removedIds = $genes->pluck('id')->values();
        $this->handle($group, $genes);
        return response()->json([
            'message' => 'Gene(s) removed successfully.',
            'removed_ids' => $removedIds,
            'count' => $removedIds->count(),
        ]);
    }
}