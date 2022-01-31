<?php

namespace App\Modules\Person\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use App\Http\Controllers\Controller;
use App\Modules\Person\Models\Person;

class PersonNotificationController extends Controller
{
    public function unread(Request $request, Person $person)
    {
        return $person->unreadNotifications;
    }
}
