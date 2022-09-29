<?php

namespace App\Modules\Person\Actions;

use DateTimeZone;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Person\Events\ProfileUpdated;
use App\Modules\Person\Policies\PersonPolicy;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Person\Actions\PersonCredentialsSync;
use App\Modules\Person\Http\Requests\ProfileUpdateRequest;

class ProfileUpdate
{
    use AsObject;
    use AsController;

    public function __construct(private PersonCredentialsSync $personSyncCredentials)
    {
        //code
    }


    public function handle(Person $person, array $data)
    {
        $person->update($data);

        if ($person->user_id) {
            $person->user->update([
                'name' => $person->first_name. ' '.$person->last_name,
                'email' => $person->email
            ]);
        }

        if (isset($data['credential_ids'])) {
            $person = $this->personSyncCredentials->handle($person, $data['credential_ids']);
        }

        Event::dispatch(new ProfileUpdated($person, $data));

        return $person;
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $profileData = $request->only(['first_name', 'last_name', 'email', 'credential_ids', 'institution_id']);
        if ($request->user()->can('update', $person)) {
            $profileData = $request->all();
        }
        $person = $this->handle($person, $profileData);

        $person->load(
            'institution',
            'credentials',
            'primaryOccupation',
            'race',
            'ethnicity',
            'gender',
            'memberships',
            'memberships.group',
            'country'
        );
        return $person;
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('update', $request->person)
            || $request->user()->can('updateNameAndEmail', $request->person);
    }

    public function rules(ActionRequest $request)
    {
        $rules = [
            'email' => [
                'required',
                'email',
                Rule::unique('people', 'email')
                    ->ignore($request->person->id),
                Rule::unique('users', 'email')
                    ->ignore($request->person->user_id),
            ],
            'first_name' => ['required','max:255'],
            'last_name' => ['required','max:255'],
            'institution_id' => ['exists:institutions,id'],
            'credential_ids' => ['array'],
            'credential_ids.*' => ['exists:credentials,id'],
            // 'race_id' => ['exists:races,id'],;
            // 'primary_occupation_id' => ['exists:primary_occupations,id'],
            // 'gender_id' => ['exists:genders,id'],
            'country_id' => ['exists:countries,id'],
            'timezone' => [Rule::in(DateTimeZone::listIdentifiers())],
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
