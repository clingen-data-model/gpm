<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Validation\Rules\File;
use App\Modules\Group\Events\WebTextUpdated;
use App\Modules\Group\Http\Resources\GroupResource;

class WebTextUpdate
{
    use AsController, AsObject;

    public function handle(Group $group, ?string $excerpt)
    {
        // Only WG
        if ($group->group_type_id !== config('groups.types.wg.id')) {
            abort(422, 'Only Working Groups support excerpt.');
        }

        $oldExcerpt = $group->excerpt;
        $newExcerpt = $excerpt;

        if ($oldExcerpt === $newExcerpt) {
            abort(422, 'The excerpt has not been changed.');
        } else {
            $group->excerpt = $newExcerpt;            
            $group->save();
            event(new WebTextUpdated($group, $oldExcerpt, $newExcerpt));
        }
        return new GroupResource($group);
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $validated = $request->validated();
        $excerpt = $request->excerpt ?? null;
        return $this->handle(
            group: $group,
            excerpt: $excerpt ?? null,
        );
    }

    public function rules(): array
    {
        return [
            'excerpt'   => ['nullable', 'string'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('update', $request->group);
    }
}
