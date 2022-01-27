<?php

namespace App\Modules\User\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return User::query()
            ->with([
                'person' => function ($q) {
                    $q->select(['id', 'first_name', 'last_name', 'email', 'user_id']);
                },
                'roles' => function ($q) {
                    $q->select('id', 'name');
                },
                'permissions' => function ($q) {
                    $q->select('id', 'name');
                }
            ])->get();
    }

    public function show(User $user)
    {
        // $user = User::findOrFail($id);
        $user->load([
            'person' => function ($q) {
                $q->select('user_id', 'first_name', 'last_name', 'email', 'id');
            },
            'roles',
            'roles.permissions',
            'permissions',
            'person.memberships',
            'person.memberships.roles',
            'person.memberships.permissions',
            'person.memberships.group' => function ($q) {
                $q->select(['id', 'name', 'group_type_id', 'group_status_id']);
            },
            'person.memberships.group.expertPanel' => function ($q) {
                $q->select(['id', 'group_id', 'long_base_name', 'current_step', 'expert_panel_type_id']);
            }
        ]);
        return $user;
    }
}
