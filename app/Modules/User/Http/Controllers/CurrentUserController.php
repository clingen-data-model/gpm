<?php

namespace App\Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\User\Http\Resources\CurrentUserResource;

class CurrentUserController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        if ($user == null) {
            abort(401);
        }
        $user->load([
            'roles',
            'roles.permissions',
            'permissions',
            'person',
            'person.memberships' => function ($q) {
                $q->isActive();
            },
            'person.memberships.group',
            'person.memberships.group.expertPanel',
            'person.memberships.group.type',
            'person.memberships.group.status',
            'person.memberships.latestCoi',
            'person.memberships.permissions',
            'person.memberships.roles',
            'person.memberships.roles.permissions',
            'person.institution',
            'person.credentials',
            'person.expertises',
            'person.country',
            'person.latestCocAttestation',
        ]);

        return new CurrentUserResource($user);
    }
}
