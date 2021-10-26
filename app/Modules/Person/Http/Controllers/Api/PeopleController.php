<?php

namespace App\Modules\Person\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Jobs\CreatePerson;
use App\Modules\Person\Jobs\UpdatePerson;
use App\Modules\Person\Http\Requests\PersonUpdateRequest;
use App\Modules\Person\Http\Requests\PersonCreationRequest;

class PeopleController extends Controller
{
    public function __construct(private Dispatcher $commandBus)
    {
    }

    public function index(Request $request)
    {
        return Person::all();
    }
    
    public function store(PersonCreationRequest $request)
    {
        if (Auth::user()->cannot('person-create')) {
            abort(403, 'You do not have permission to create people.');
        }
        $data = $request->only('first_name', 'last_name', 'email', 'phone', 'uuid');
        $this->commandBus->dispatch(new CreatePerson(...$data));
        $person = Person::findByUuid($request->uuid);
        
        if (!$person) {
            throw new Exception('Failed to find newly created person');
        }

        return $person;
    }
    
    public function show($uuid)
    {
        $person = Person::findByUuidOrFail($uuid);
        $person->load([
            'memberships', 
            'memberships.group', 
            'memberships.roles', 
            'memberships.permissions', 
            'memberships.group.type',
            'institution',
            'primaryOccupation',
            'country',
            'race',
            'ethnicity',
            'gender',
            'invite'
        ]);
        return $person;
    }

    public function update($uuid, PersonUpdateRequest $request)
    {
        $data = $request->only('first_name', 'last_name', 'email', 'phone');
        $this->commandBus->dispatch(new UpdatePerson(uuid: $uuid, attributes: $data));

        $person = Person::findByUuidOrFail($uuid);

        return $person;
    }
}
