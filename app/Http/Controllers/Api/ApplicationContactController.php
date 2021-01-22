<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Domain\Application\Models\Person;
use App\Domain\Application\Jobs\AddContact;
use App\Domain\Application\Models\Application;
use App\Http\Requests\ApplicationContactRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApplicationContactController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {}

    public function index($uuid)
    {
        $application = Application::findByUuidOrFail($uuid);

        return $application->contacts;
    }

    public function store($uuid, ApplicationContactRequest $request)
    {
        $application = Application::findByUuidOrFail($uuid);
        if (!$application) {
            throw new ModelNotFoundException();
        }

        $person = Person::firstOrCreate(
            ['email' => $request->email],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone
            ]);

        $job = new AddContact(application: $application, person: $person);

        $this->dispatcher->dispatchNow($job);
        
        return $person;
    }
    
}
