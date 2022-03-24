<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\ModelSearchService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Http\Resources\InviteResource;

class InviteController extends Controller
{
    public function index(Request $request)
    {
        $sortFunction =  function ($query, $field, $dir) {
            if ($field == 'person.name' || $field == 'person.email') {
                $query->join('people', 'invites.person_id', '=', 'people.id');
                if ($field == 'person.name') {
                    $query->orderBy('people.first_name', $dir)
                        ->orderBy('people.last_name', $dir);
                } else {
                    $query->orderBy('people.email');
                }
                return $query;
            }

            if ($field == 'inviter') {
                $query->join('groups', 'invites.inviter_id', '=', 'groups.id')
                    ->orderBy('groups.name', $dir);

                return $query;
            }

            $query->orderBy($field, $dir);
            return $query;

            \Log::debug(renderQuery($query));
        };

        $whereFunction = function ($query, $where) {
            foreach ($where as $key => $value) {
                if ($key == 'keyword') {
                    $query->leftJoin('people', 'invites.person_id', '=', 'people.id')
                        ->leftJoin('groups', 'invites.inviter_id', '=', 'groups.id')
                        ->where(function ($query) use ($value) {
                            $query->where('people.first_name', 'like', '%'.$value.'%')
                                ->orWhere('people.last_name', 'like', '%'.$value.'%')
                                ->orWhere('people.email', 'like', '%'.$value.'%')
                                ->orWhere('groups.name', 'like', '%'.$value.'%');
                        });
                        continue;
                    }
                if (is_array($value)) {
                    $query->whereIn($key, $value);
                } else {
                    $query->where($key, $value);
                }

            }    
            return $query;
        };

        $search = new ModelSearchService(
            modelClass: Invite::class,
            defaultWith: ['person', 'inviter'],
            defaultSelect: ['invites.id', 'redeemed_at', 'inviter_id', 'inviter_type', 'code', 'person_id'],
            sortFunction: $sortFunction,
            whereFunction: $whereFunction
        );
        
        \Log::debug(renderQuery($search->buildQuery($request->all())));

        $invites = $search->buildQuery($request->all())
                    ->paginate($request->get('page_size', 20));
        return InviteResource::collection($invites);
    }
}
