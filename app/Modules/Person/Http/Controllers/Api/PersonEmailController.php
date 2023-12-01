<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Person\Models\Person;
use Illuminate\Bus\Dispatcher;
use Illuminate\Http\Request;

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
