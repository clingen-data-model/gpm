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
                }
                $query->orderBy('people.email');

                return $query;
            }

            if ($field == 'inviter') {
                $query->join('groups', 'invites.inviter_id', '=', 'groups.id')
                    ->orderBy('groups.name', $dir);

                return $query;
            }

            $query->orderBy($field, $dir);
            return $query;
        };

        $whereFunction = function ($query, $where) {
            foreach ($where as $key => $value) {
                if ($key == 'keyword') {
                    $query->whereHas('person', function ($q) use ($value) {
                        $q->where('first_name', 'like', '%'.$value.'%')
                        ->orWhere('last_name', 'like', '%'.$value.'%')
                        ->orWhere('email', 'like', '%'.$value.'%');
                    })
                    ->orWhereHas('inviter', function ($q) use ($value) {
                        $q->where('name', 'like', '%'.$value.'%');
                    });
                    continue;
                }

                if (is_array($value)) {
                    $query->whereIn($key, $value);
                    continue;
                }
                $query->where($key, $value);
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
        
        $invites = $search->buildQuery($request->all())
                    ->paginate($request->get('page_size', 20));
        return InviteResource::collection($invites);
    }
}
