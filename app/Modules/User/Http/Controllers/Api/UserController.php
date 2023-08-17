<?php

namespace App\Modules\User\Http\Controllers\Api;

use App\ModelSearchService;
use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = new ModelSearchService(
            modelClass: User::class,
            defaultWith: [
                'person' => function ($q) {
                    $q->select(['id', 'first_name', 'last_name', 'email', 'user_id']);
                },
                'roles' => function ($q) {
                    $q->select('id', 'name');
                },
                'permissions' => function ($q) {
                    $q->select('id', 'name');
                }
            ],
            whereFunction: function ($query, $where) {
                foreach ($where as $key => $value) {
                    if ($key == 'filterString') {
                        $query->where('users.email', 'like', '%'.$value.'%')
                            ->orWhere('users.name', 'like', '%'.$value.'%')
                            ->orWhereHas('person', function ($q) use ($value) {
                                $q->where('first_name', 'like', '%'.$value.'%')
                                    ->orWhere('last_name', 'like', '%'.$value.'%')
                                    ->orWhere('email', 'like', '%'.$value.'%');
                            })
                            ->orWhereHas('roles', function ($q) use ($value) {
                                $q->where('display_name', 'like', '%'.$value.'%');
                            })
                            ->orWhereHas('permissions', function ($q) use ($value) {
                                $q->where('display_name', 'like', '%'.$value.'%');
                            });
                        continue;
                    } elseif (is_array($value)) {
                        $query->whereIn($key, $value);
                        continue;
                    }
                    $query->where($key, $value);
                }
                return $query;
            },
            sortFunction: function ($query, $field, $dir) {
                if ($field == 'person.name') {
                    $query->leftJoin('people', 'users.id', '=', 'people.user_id');
                    if (substr($field, 7) == 'name') {
                        $query->orderBy('people.last_name', $dir)
                            ->orderBy('people.first_name', $dir);
                    }
                    if (substr($field, 7) == 'email') {
                        $query->orderBy('people.email', $dir);
                    }
                    return $query;
                }
                return $query->orderBy($field, $dir);
            }
        );

        $searchQuery = $search->buildQuery($request->only(['where', 'sort', 'with']));
        if ($request->page_size || $request->page) {
            return UserResource::collection($searchQuery->paginate($request->get('page_size', 20)));
        }


        return UserResource::collection($searchQuery->get($request->all()));
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
