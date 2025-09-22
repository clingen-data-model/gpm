<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use Illuminate\Http\Request;

class PublicationUpdate
{
    public function __invoke(Request $request, Group $group, Publication $publication)
    {
        $this->authorize($request->user(), $group);
        abort_if($publication->group_id !== $group->id, 404);

        $data = $request->validate([
            'pub_type'     => 'nullable|in:preprint,published',
            'published_at' => 'nullable|date',
            'meta'         => 'nullable|array',
            'status'       => 'nullable|in:pending,enriched,failed',
            'error'        => 'nullable|string|max:1000',
        ]);

        $publication->fill($data + ['updated_by_id' => optional($request->user())->id])->save();

        return $publication->fresh();
    }

    protected function authorize($user, Group $group): void
    {
        if (optional($user)->cannot('groups-manage') && optional($user)->cannot('application-edit', $group)) {
            abort(403);
        }
    }
}
