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
                        if (preg_match('/@/', $value)) {
                            $query->WhereFullText(['email'], preg_replace('/\./', '', $value), [], 'or');
                        } else {
                            $query->whereFullText(['name'], $value.'*', ['mode' => 'boolean'])
                                ->orWhereFullText(['email'], $value.'*', ['mode' => 'boolean']);

                        }
                    } elseif (is_array($value)) {
                        $query->whereIn($key, $value);
                    } else {
                        $query->where($key, $value);
                    }
                }
                return $query;
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
