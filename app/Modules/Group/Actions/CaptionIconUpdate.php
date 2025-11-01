<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Validation\Rules\File;
use App\Modules\Group\Events\CaptionIconUpdated;

class CaptionIconUpdate
{
    use AsController, AsObject;

    public function handle(Group $group, ?string $caption, $iconFile = null): Group
    {
        // Only WG
        if ($group->group_type_id !== config('groups.types.wg.id')) {
            abort(422, 'Only Working Groups support caption and icon.');
        }

        $changes = [];

        if (!is_null($caption)) {
            $clean = trim(strip_tags($caption));
            $clean = mb_substr($clean, 0, 250);
            if ($group->caption !== $clean) {
                $group->caption = $clean;
                $changes['caption'] = $clean;
            }
        }

        if ($iconFile) {
            $ext = strtolower($iconFile->extension() ?: $iconFile->guessExtension() ?: 'png');
            $ext = $ext === 'jpeg' ? 'jpg' : $ext;

            $filename = $group->uuid . '.' . $ext;
            $dir = config('app.workinggroups_icon');

            // BEFORE STORING, DELETE EXISTING FILE WITH THE SAME UUID DIFF EXT. JUST IN CASE
            foreach (config('app.wg_icon_exts') as $e) {
                if ($e === $ext) continue;
                Storage::disk('public')->delete($dir . '/' . $group->uuid . '.' . $e);
            }

            $path = $iconFile->storeAs($dir, $filename, 'public');
            if (!$path || !Storage::disk('public')->exists($path)) {
                abort(500, 'Failed to store icon file.');
            }

            if ($group->icon_path !== $filename) {
                $group->icon_path = $filename;
                $group->touch();
                $changes['icon_url'] = $group->icon_url;
            }
        }        

        if (!empty($changes)) {            
            $group->save();
            event(new CaptionIconUpdated($group, $changes));
        }

        return $group->fresh();
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $validated = $request->validated();
        $caption = $request->caption ?? null;
        $icon = $request->file('icon');
        return $this->handle(
            group: $group,
            caption: $caption ?? null,
            iconFile: $request->file('icon')
        );
    }

    public function rules(): array
    {
        return [
            'caption'   => ['nullable', 'string', 'max:250'],
            'icon'      => ['nullable', File::image()->max(3 * 1024)->types(['png', 'jpg', 'gif', 'jpeg'])], // MAX 3MB
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('update', $request->group);
    }
}
