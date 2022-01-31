<?php

namespace App\Modules\Person\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Http\Resources\PersonDetailResource;

class PeopleController extends Controller
{
    public function __construct(private Dispatcher $commandBus)
    {
    }

    public function index(Request $request)
    {
        return Person::all();
    }
        
    public function show(Person $person)
    {
        $person->load([
            'memberships',
            'memberships.cois',
            'memberships.group',
            'memberships.group.expertPanel',
            'memberships.group.type',
            'memberships.latestCoi',
            'memberships.permissions',
            'memberships.roles',
            'institution',
            'primaryOccupation',
            'country',
            'race',
            'ethnicity',
            'gender',
            'invite'
        ]);
        return new PersonDetailResource($person);
    }
}
