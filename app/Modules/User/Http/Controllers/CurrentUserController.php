<?php

namespace App\Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\User\Http\Resources\CurrentUserResource;

class CurrentUserController extends Controller
{
    public function show()
    {
        if (Auth::guest()) {
            return abort(401);
        }
        $userId = Auth::id();
        $user = User::find($userId);
        $user->load([
            'roles',
            'roles.permissions',
            'permissions',
            'person',
            'person.memberships' => function ($q) {
                $q->isActive();
            },
            'person.memberships.cois',
            'person.memberships.group',
            'person.memberships.group.expertPanel',
            'person.memberships.group.type',
            'person.memberships.group.status',
            'person.memberships.latestCoi',
            'person.memberships.permissions',
            'person.memberships.roles',
            'person.memberships.roles.permissions',
            'person.ethnicity',
            'person.race',
            'person.gender',
            'person.primaryOccupation',
            'person.institution',
            'preferences'
        ]);

        return new CurrentUserResource($user);
    }
}
