<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\PersonIdentity;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonIdentityDelete
{
    use AsController;

    public function asController(ActionRequest $request, Person $person, PersonIdentity $identity)
    {
        $identity->delete();

        return response()->noContent();
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('update', $request->person);
    }
}
