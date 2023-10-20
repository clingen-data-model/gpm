<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\ContactRemoved;
use App\Modules\ExpertPanel\Exceptions\PersonNotContactException;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Person\Models\Person;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class ContactRemove
{
    use AsAction;

    /**
     * Execute the job.
     */
    public function handle(string $expertPanelUuid, string $personUuid): void
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
            Log::warning($e->getMessage(), ['application_id' => $e->getApplication()->id, 'person_id' => $e->getPerson()->id]);

            throw ValidationException::withMessages([
                'contact' => ['The specified person is not a contact of this application.'],
            ]);
        }

        return response(['message' => 'deleted person '.$personUuid.' from application '.$expertPanelUuid]);
    }
}
