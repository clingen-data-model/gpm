<?php

namespace App\Modules\Person\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use App\Http\Controllers\Controller;
use App\ModelSearchService;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Http\Resources\PersonDetailResource;

class PeopleController extends Controller
{
    public function index(Request $request)
    {
        $search = new ModelSearchService(
            modelClass: Person::class,
            defaultSelect: ['first_name', 'last_name', 'email', 'people.id', 'institution_id', 'people.uuid'],
            defaultWith: [
                'institution' => function ($q) {
                    $q->select('name', 'id');
                }
            ],
            whereFunction: function ($query, $where) {
                foreach ($where as $key => $value) {
                    if ($key == 'filterString') {
                        $query->leftJoin('institutions', 'institutions.id', '=', 'people.institution_id')
                            ->where(function ($q) use ($value) {
                                $q->where('first_name', 'like', '%'.$value.'%')
                                    ->orWhere('last_name', 'like', '%'.$value.'%')
                                    ->orWhereRaw('concat(first_name, " ", last_name) like "%'.$value.'%"')
                                    ->orWhere('email', 'like', '%'.$value.'%')
                                    ->orWhere('institutions.name', '%'.$value.'%');
                            });
                    } elseif (is_array($value)) {
                        $query->whereIn($key, $value);
                    } else {
                        $query->where($key, $value);
                    }
                }
                return $query;
            }
        );

        return $search->search($request->only(['where', 'sort', 'with', 'showDeleted']));
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
