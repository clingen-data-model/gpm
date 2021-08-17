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
        $expertPanel = ExpertPanel::findByUuidOrFail($uuid);
        $expertPanel->fill($attributes);
        if ($expertPanel->isDirty()) {
            $updatedAttributes = $expertPanel->getDirty();
            $expertPanel->save();
            Event::dispatch(new ExpertPanelAttributesUpdated($expertPanel, $updatedAttributes));
        }
        
        return $expertPanel;
    }

    public function asController(string $uuid, UpdateExpertPanelAttributesRequest $request)
    {
        return $this->handle($uuid, $request->all());
    }
    
}
