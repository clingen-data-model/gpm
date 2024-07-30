<?php

namespace App\Modules\Person\Actions;

use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Person\Events\ProfileUpdated;
use Lorisleiva\Actions\Concerns\AsController;

// FIXME: should actually handle validation with rules, etc. A lot of this was just copied from PersonUpdate without modification
// FIXME: I've done some cleanup, but there's still a lot of work to do here to be idomatic Laravel and for validation, etc. -bpow

class DemographicsUpdate
{
    use AsObject;
    use AsController;

    public function handle(Person $person, array $data)
    {
        $data['demographics_completed_date'] = now();
        $person->update($data);

        Event::dispatch(new ProfileUpdated($person, $data));

        return response('The demographics update was successful.', 200)
            ->header('Content-Type', 'text/plain');
    }

    public function asController(ActionRequest $request, Person $person)
    {
        // would return a 403 if the user is not authorized
        Gate::authorize('viewDemographics', $request->person);

        // only allow the user to update demographics fields through this controller
        $updated_data = [];
        foreach (Person::$demographics_private_fields as $field) {
            if (isset($request->$field)) {
                $updated_data[$field] = $request->$field;
            }
        }
        $response = $this->handle($person, $updated_data);

        return $response;
    }

    public function authorize(ActionRequest $request): bool
    {
        // TODO- possibly should rename that gate since it's not just for viewing
        return Gate::allows('viewDemographics', $request->person);
    }

    public function rules(ActionRequest $request)
    {
        // FIXME: this relates to code that is part of PersonUpdate, but not DemographicsUpdate
        $rules = [
            'street1' => ['nullable','max:255'],
            'street2' => ['nullable','max:255'],
            'city' => ['nullable','max:255'],
            'state' => ['nullable','max:255'],
            'zip' => ['nullable','max:255'],
        ];

        if ($request->person->user_id == Auth::user()->id) {
            foreach ($rules as $field => $rule) {
                if (in_array('nullable', $rule)) {
                    continue;
                }
                array_unshift($rules[$field], 'required');
            }
            return $rules;
        }

        foreach ($rules as $field => $rule) {
            $rules[$field][] = 'nullable';
        }
        return $rules;
    }

    public function getValidationMessages(): array
    {
        return [
            'required' => 'This is required.',
            'exists' => 'The selection is invalid.',
            'email.unique' => 'Somebody is already using that email address.'
        ];
    }

}
