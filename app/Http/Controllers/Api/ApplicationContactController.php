<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddContactRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Jobs\AddContact;
use App\Modules\ExpertPanel\Jobs\RemoveContact;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\ApplicationContactRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Modules\ExpertPanel\Exceptions\PersonNotContactException;

class ApplicationContactController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {}

    public function index($expertPanelUuid)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);

        return $expertPanel->contacts;
    }

    public function store($expertPanelUuid, AddContactRequest $request)
    {
        $job = new AddContact(
            expertPanelUuid: $expertPanelUuid,
            uuid: $request->person_uuid,
        );

        $this->dispatcher->dispatchNow($job);
       
        $person = ExpertPanel::findByUuid($expertPanelUuid)->contacts()->where('uuid', $request->person_uuid)->sole();

        return $person;
    }

    public function remove($expertPanelUuid, $personUuid)
    {
        try {
            $job = new RemoveContact(expertPanelUuid: $expertPanelUuid, personUuid: $personUuid);
            $this->dispatcher->dispatch($job);
        } catch (PersonNotContactException $e) {
            \Log::warning($e->getMessage(), ['appliation_id' => $e->getApplication()->id, 'person_id' => $e->getPerson()->id]);
            throw ValidationException::withMessages([
                'contact' => ['The specified person is not a contact of this application.']
            ]);
        }

        return response(['message'=>'deleted person '.$personUuid.' from appliation '.$expertPanelUuid], 200);
    }
    
}
