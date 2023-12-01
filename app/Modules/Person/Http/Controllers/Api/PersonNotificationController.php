<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Person\Models\Person;
use Illuminate\Http\Request;

class PersonNotificationController extends Controller
{
    public function unread(Request $request, Person $person)
    {
        return $person->unreadNotifications;
    }
}
