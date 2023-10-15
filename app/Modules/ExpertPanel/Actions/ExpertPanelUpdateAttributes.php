<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;
use App\Modules\ExpertPanel\Http\Requests\UpdateExpertPanelAttributesRequest;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class ExpertPanelUpdateAttributes
{
    use AsAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function handle(
        string $uuid,
        array $attributes
    ): void {
        $attributes = collect($attributes);
        $expertPanel = ExpertPanel::findByUuidOrFail($uuid);
        $expertPanel->fill($attributes->toArray());
        if ($expertPanel->isDirty()) {
            $updatedAttributes = $expertPanel->getDirty();
            $expertPanel->save();
            Event::dispatch(new ExpertPanelAttributesUpdated($expertPanel, $updatedAttributes));

            if ($expertPanel->long_base_name) {
                $expertPanel->group->update(['name' => $expertPanel->long_base_name]);
            }
        }

        if ($attributes->get('cdwg_id')) {
            $groupAttributes = [];
            if (isset($attributes['cdwg_id'])) {
                $groupAttributes['parent_id'] = $attributes['cdwg_id'];
            }
            $expertPanel->group->update($groupAttributes);
        }

        return $expertPanel;
    }

    public function asController(string $uuid, UpdateExpertPanelAttributesRequest $request)
    {
        $data = $request->only('long_base_name', 'short_base_name', 'affiliation_id', 'cdwg_id');

        return $this->handle($uuid, $data);
    }
}
