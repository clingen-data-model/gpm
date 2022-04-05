<?php

namespace App\Modules\Person\Http\Controllers\Api;

use Exception;
use App\ModelSearchService;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Http\Resources\PersonResource;
use App\Modules\Person\Http\Resources\PersonDetailResource;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $search = new ModelSearchService(
            modelClass: Person::class,
            defaultSelect: ['people.id as id', 'first_name', 'last_name', 'email', 'people.id', 'institution_id', 'people.uuid'],
            defaultWith: [
                'institution' => function ($q) {
                    $q->select('name', 'id');
                }
            ],
            whereFunction: function ($query, $where) {
                foreach ($where as $key => $value) {
                    if (!$value) {
                        continue;
                    }
                    if ($key == 'filterString') {
                        $words = explode(' ', $value);
                        foreach ($words as $word) {
                            $query->Where('first_name', 'like', '%'.$word.'%', 'or')
                                ->orWhere('last_name', 'like', '%'.$word.'%', 'or')
                                ->orWhere('email', 'like', '%'.$word.'%', 'or');
                        }
                    } elseif ($key == 'first_name') {
                        $query->where('first_name', 'like', '%'.$value.'%');
                    } elseif ($key == 'last_name') {
                        $query->where('last_name', 'like', '%'.$value.'%');
                    } elseif ($key == 'email') {
                        $query->where('email', 'like', '%'.$value.'%');
                    } elseif (is_array($value)) {
                        $query->whereIn($key, $value);
                    } else {
                        $query->where($key, $value);
                    }
                }

                return $query;
            },
            sortFunction: function ($query, $field, $dir) {
                if ($field == 'institution.name') {
                    return $query->join('institutions', 'institutions.id', '=', 'people.institution_id')
                        ->orderBy('institutions.name', $dir);
                }
                
                if ($field == 'name') {
                    return $query->orderBy('first_name', $dir)
                        ->orderBy('last_name', $dir);
                }
                

                return $query->orderBy($field, $dir);    
            }
        );
        
        $searchQuery = $search->buildQuery($request->only(['where', 'sort', 'with']));
        if ($request->page_size || $request->page) {
            return PersonResource::collection($searchQuery->paginate($request->get('page_size', 20)));
        }

        return PersonResource::collection($searchQuery->get($request->all()));
    }
        
    public function show(Person $person)
    {
        $person->load([
            'memberships',
            'memberships.cois',
            'memberships.group',
            'memberships.group.expertPanel',
            'memberships.group.type',
            'memberships.latestCoi',
            'memberships.permissions',
            'memberships.roles',
            'institution',
            'primaryOccupation',
            'country',
            'race',
            'ethnicity',
            'gender',
            'invite'
        ]);
        return new PersonDetailResource($person);
    }
}
