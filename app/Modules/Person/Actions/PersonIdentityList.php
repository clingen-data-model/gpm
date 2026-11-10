<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonIdentityList
{
    use AsController;

    public function asController(ActionRequest $request, Person $person)
    {
        return $person->identities()
            ->orderBy('created_at')
            ->get(['id', 'idp', 'idp_name', 'email', 'orcid', 'last_login_at', 'created_at']);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('update', $request->person);
    }
}
