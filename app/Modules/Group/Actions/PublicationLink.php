<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PublicationLink extends PublicationAdd
{
    public function __invoke(Request $request, Group $group, ?Publication $publication = null)
    {
        $this->authorize($request->user(), $group);
        abort_unless($publication && $publication->group_id === $group->id, 404);
        $data = $request->validate(['published_publication_id' => 'present|nullable|integer']);
        DB::transaction(function () use ($publication, $data) {
            $targetId = $data['published_publication_id'];
            // Lock endpoints in a consistent order to serialize competing links.
            $rows = Publication::whereIn('id', array_filter([$publication->id, $targetId]))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $rows->get($publication->id);
            $target = $rows->get($targetId);
            if (!$source || ($targetId !== null && (!$target || $source->id === $target->id))) {
                throw ValidationException::withMessages(['published_publication_id' => 'Choose another existing publication.']);
            }
            if ($target) {
                Gate::authorize('view', $target->group);
                if ($target->published_publication_id || Publication::where('published_publication_id', $source->id)->exists()
                    || Publication::where('published_publication_id', $target->id)->where('id', '!=', $source->id)->exists()) {
                    throw ValidationException::withMessages(['published_publication_id' => 'A publication can belong to only one preprint/article pair.']);
                }
            }
            $source->published_publication_id = $targetId;
            $source->save();
        }, 3);
        return response()->noContent();
    }
}
