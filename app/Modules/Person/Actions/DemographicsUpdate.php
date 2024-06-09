<?php

namespace App\Modules\Person\Actions;

use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Person\Events\ProfileUpdated;
use Lorisleiva\Actions\Concerns\AsController;

// FIXME: should actually handle validation with rules, etc. A lot of this was just copied from PersonUpdate without modificaiton

class DemographicsUpdate
{
    use AsObject;
    use AsController;

    public function handle(Person $person, array $data)
    {
        $person->update($data);

        Event::dispatch(new ProfileUpdated($person, $data));


        return response('The demographics update was successful.', 200)
            ->header('Content-Type', 'text/plain');
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $demoData = $request->only(['id']);
        // TODO: should this check for advanced logic regarding admins/impersonation?
        if ($request->user()->can('update', $person)) {
            $demoData = $request->all();
        }
        $person = $this->handle($person, $demoData);

        return $person;
    }

    public function authorize(ActionRequest $request): bool
    {
        // TODO: should this check for advanced logic regarding admins/impersonation?
        return $request->user()->can('update', $request->person)
            || $request->user()->can('updateNameAndEmail', $request->person);
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
