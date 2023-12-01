<?php

namespace App\Http\Controllers;

use App\Http\Resources\ImpersonatableUserResource;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;

class ImpersonateSearchController extends Controller
{
    public function index(Request $request)
    {
        $userQuery = User::query()
                    ->with('roles')
                    ->select('name', 'email', 'id')
                    ->where(function ($query) use ($request) {
                        $pattern = '%'.$request->query_string.'%';
                        $query->where('name', 'LIKE', $pattern)
                            ->orWhere('email', 'LIKE', $pattern)
                            ->orWhere('id', 'LIKE', $pattern);
                    });

        $users = $userQuery->get();

        return ImpersonatableUserResource::collection($users->filter(function ($u) {
            return $u->canBeImpersonated();
        }));
    }
}
