<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use App\Modules\Group\Events\PublicationAdded;
use Illuminate\Http\Request;

class PublicationAdd
{
    public function __invoke(Request $request, Group $group)
    {
        $this->authorize($request->user(), $group);

        $data = $request->validate([
            'source'        => 'required|string|in:pmid,pmcid,doi,url',
            'identifier'    => 'required|string|max:512',
            'link'          => 'nullable|url|max:2048',
            'pub_type'      => 'nullable|string|max:250',
            'published_at'  => 'nullable|date',
            'meta'          => 'nullable|array',
        ]);

        $pub = Publication::firstOrCreate(
            ['group_id' => $group->id, 'source' => $data['source'], 'identifier' => $data['identifier']],
            [
                'added_by_id' => optional($request->user())->id,
                'link'         => $data['link'],
                'pub_type'     => $data['pub_type'],
                'published_at' => $data['published_at'],
                'meta'         => $data['meta'],
            ]
        );

        event(new PublicationAdded($group, $pub->fresh()));
        $pub->forceFill(['sent_to_dx_at' => now()])->save();

        return response()->json($pub, 200);
    }

    protected function authorize($user, Group $group): void
    {
        if (optional($user)->cannot('groups-manage') && optional($user)->cannot('application-edit', $group)) {
            abort(403);
        }
    }
}
