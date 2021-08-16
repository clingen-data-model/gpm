<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ContactAdded;
use App\Modules\ExpertPanel\Http\Requests\AddContactRequest;

class ContactAdd
{
    use AsAction;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        string $expertPanelUuid,
        string $uuid
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        
        $person = Person::findByUuidOrFail($uuid);

        if ($expertPanel->contacts()->get()->contains($person)) {
            return;
        }

        $expertPanel->contacts()->attach($person);
        $expertPanel->touch();
        Event::dispatch(new ContactAdded($expertPanel, $person));

        return $person;
    }

    public function asController($epUuid, AddContactRequest $request)
    {
        $this->handle(
            expertPanelUuid: $epUuid,
            uuid: $request->person_uuid,
        );

        $person = ExpertPanel::findByUuid($epUuid)->contacts()->where('uuid', $request->person_uuid)->sole();

        return $person;
    }
}
