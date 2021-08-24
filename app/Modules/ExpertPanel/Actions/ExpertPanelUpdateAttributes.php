<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;
use App\Modules\ExpertPanel\Http\Requests\UpdateExpertPanelAttributesRequest;

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
    ) {
        $attributes = collect($attributes);
        $expertPanel = ExpertPanel::findByUuidOrFail($uuid);
        $expertPanel->fill($attributes->except('cdwg_id', 'working_name')->toArray());
        if ($expertPanel->isDirty()) {
            $updatedAttributes = $expertPanel->getDirty();
            $expertPanel->save();
            Event::dispatch(new ExpertPanelAttributesUpdated($expertPanel, $updatedAttributes));
        }

        if ($attributes->get('working_name') || $attributes->get('cdwg_id')) {
            $groupAttributes = [];
            if (isset($attributes['working_name'])) {
                $groupAttributes['name'] = $attributes['working_name'];
            }
            if (isset($attributes['cdwg_id'])) {
                $groupAttributes['parent_id'] = $attributes['cdwg_id'];
            }
            $expertPanel->group->update($groupAttributes);
        }
        
        return $expertPanel;
    }

    public function asController(string $uuid, UpdateExpertPanelAttributesRequest $request)
    {
        $data = $request->only('working_name', 'long_base_name', 'short_base_name', 'affiliation_id', 'cdwg_id');
        return $this->handle($uuid, $data);
    }
}
