<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ContactRemoved;
use App\Modules\ExpertPanel\Exceptions\PersonNotContactException;

class ContactRemove
{
    use AsAction;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(string $expertPanelUuid, string $personUuid)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $person = Person::findByUuidOrFail($personUuid);
        $contact = $expertPanel
                    ->contacts()
                    ->whereHas('person', function ($q) use ($personUuid) {
                        $q->where('uuid', $personUuid);
                    })->first();

        if (! $contact) {
            throw new PersonNotContactException($expertPanel, $person);
        }

        $contact->forceDelete($contact);
        $expertPanel->touch();
        Event::dispatch(new ContactRemoved($expertPanel, $person));
    }

    public function asController($expertPanelUuid, $personUuid): Response
    {
        try {
            $this->handle($expertPanelUuid, $personUuid);
        } catch (PersonNotContactException $e) {
            Log::warning($e->getMessage(), ['appliation_id' => $e->getApplication()->id, 'person_id' => $e->getPerson()->id]);
            throw ValidationException::withMessages([
                'contact' => ['The specified person is not a contact of this application.']
            ]);
        }

        return response(['message'=>'deleted person '.$personUuid.' from appliation '.$expertPanelUuid], 200);
    }
}
