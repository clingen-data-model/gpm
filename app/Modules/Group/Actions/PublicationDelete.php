<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use Illuminate\Http\Request;
use App\Modules\Group\Events\PublicationDeleted;

class PublicationDelete
{
    public function __invoke(Request $request, Group $group, Publication $publication)
    {
        $this->authorize($request->user(), $group);
        abort_if($publication->group_id !== $group->id, 404);

        $toLog = $publication->replicate();
        $publication->delete();
        event(new PublicationDeleted($group, $toLog));

        return response()->noContent();
    }

    protected function authorize($user, Group $group): void
    {
        if (optional($user)->cannot('groups-manage') && optional($user)->cannot('application-edit', $group)) {
            abort(403);
        }
    }
}
