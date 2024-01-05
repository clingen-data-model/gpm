<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonDemographicsStore
{
    use AsController;

    public function handle(ActionRequest $request, Person $person): string
    {
        $person->profile_demographics = $request->get('demographics');
        $person->save();
        return $person->profile_demographics;
    }

    public function authorize(ActionRequest $request): bool
    {
        // TODO: should probably be more specific-- maybe a separate role for managing profile demographics?
        return $request->user()->can('people-manage') || $request->user()->isLinkedToPerson($request->person);
    }

    public function rules(): array
    {
        return [
            'demographics' => 'required|json',
        ];
    }

}
