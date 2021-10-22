<?php

namespace App\Modules\Person\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Http\Resources\InviteResource;

class InviteController extends Controller
{
    public function index(Request $request)
    {
        $invites = Invite::with('person', 'inviter')
                    ->get();
        return InviteResource::collection($invites);
    }
}
