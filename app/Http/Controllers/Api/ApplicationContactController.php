<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Domain\Application\Models\Person;
use App\Domain\Application\Jobs\AddContact;
use App\Domain\Application\Jobs\RemoveContact;
use App\Domain\Application\Models\Application;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\ApplicationContactRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Domain\Application\Exceptions\PersonNotContactException;

class ApplicationContactController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {}

    public function index($applicationUuid)
    {
        $application = Application::findByUuidOrFail($applicationUuid);

        return $application->contacts;
    }

    public function store($applicationUuid, ApplicationContactRequest $request)
    {
        $job = new AddContact(
            applicationUuid: $applicationUuid,
            uuid: $request->uuid,
            first_name: $request->first_name,
            last_name: $request->last_name,
            email: $request->email,
            phone: $request->phone,
        );

        $this->dispatcher->dispatchNow($job);
       
        $person = Application::findByUuid($applicationUuid)->contacts()->where('uuid', $request->uuid)->sole();

        return $person;
    }

    public function remove($applicationUuid, $personUuid)
    {
        try {
            $job = new RemoveContact(applicationUuid: $applicationUuid, personUuid: $personUuid);
            $this->dispatcher->dispatch($job);
        } catch (PersonNotContactException $e) {
            \Log::warning($e->getMessage(), ['appliation_id' => $e->getApplication()->id, 'person_id' => $e->getPerson()->id]);
            throw ValidationException::withMessages([
                'contact' => ['The specified person is not a contact of this application.']
            ]);
        }

        return response(['message'=>'deleted person '.$personUuid.' from appliation '.$applicationUuid], 200);
    }
    
}
