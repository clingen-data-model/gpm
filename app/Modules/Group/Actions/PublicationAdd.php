<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use App\Modules\Group\Service\PublicationLookup;
use App\Modules\Group\Events\PublicationAdded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendPublicationAdded;
use App\Jobs\EnrichPublication;

class PublicationAdd
{
    public function __invoke(Request $request, Group $group)
    {
        $this->authorize($request->user(), $group);

        $data = $request->validate([
            'entries'   => 'required|array|min:1',
            'entries.*' => 'string|max:512',
        ]);

        $ids = [];
        foreach ($data['entries'] as $raw) {
            $raw = trim($raw);
            if ($raw === '') continue;

            [$source, $identifier] = PublicationLookup::normalize($raw);

            $pub = Publication::firstOrCreate(
                ['group_id' => $group->id, 'source' => $source, 'identifier' => $identifier],
                ['added_by_id' => optional($request->user())->id, 'status' => 'pending']
            );            
            
            DB::afterCommit(function () use ($pub, $group) {
                Bus::chain([
                    new EnrichPublication($pub->id),
                    new SendPublicationAdded($group->id, $pub->id),
                ])->dispatch();
            });

            
            $ids[] = $pub->id;
        }        
        return response()->json(['ids' => $ids], 202);
    }

    protected function authorize($user, Group $group): void
    {
        if (optional($user)->cannot('groups-manage') && optional($user)->cannot('application-edit', $group)) {
            abort(403);
        }
    }
}
