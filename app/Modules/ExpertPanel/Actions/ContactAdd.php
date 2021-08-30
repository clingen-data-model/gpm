<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ContactAdded;
use App\Modules\ExpertPanel\Http\Requests\AddContactRequest;
use App\Modules\Group\Models\GroupMember;

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

        
        $expertPanel->addContact($person);
        Event::dispatch(new ContactAdded($expertPanel, $person));

        return $person;
    }

    public function asController($epUuid, AddContactRequest $request)
    {
        $person = $this->handle(
            expertPanelUuid: $epUuid,
            uuid: $request->person_uuid,
        );

        return $person;
    }
}
