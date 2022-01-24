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
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Person\Http\Requests\ProfileUpdateRequest;
use App\Modules\Person\Policies\PersonPolicy;

class ProfileUpdate
{
    use AsObject;
    use AsController;

    public function handle(Person $person, array $data)
    {
        $policy = new PersonPolicy();
        if (Auth::guest()) {
            abort(403);
        }
        // if (!$policy->update(Auth::user(), $person)) {
        if (Auth::user()->cannot('update', $person)) {
            abort(403, 'You do not have permission to update this person\'s profile.');
        }
        $person->update($data);

        Event::dispatch(new ProfileUpdated($person, $data));

        return $person;
    }

    public function asController(ProfileUpdateRequest $request, $personUuid)
    {
        $person = Person::findByUuidOrFail($personUuid);
        $person = $this->handle($person, $request->all());

        $person->load(
            'institution',
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
}
