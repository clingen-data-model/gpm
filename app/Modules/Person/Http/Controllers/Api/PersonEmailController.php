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

class PersonEmailController extends Controller
{
    public function __construct(private Dispatcher $commandBus)
    {
    }

    public function index(Request $request, Person $person)
    {
        return $person->emails;
    }
}
