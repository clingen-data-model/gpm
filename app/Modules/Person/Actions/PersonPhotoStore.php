<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Events\ProfileUpdated;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonPhotoStore
{
    use AsController;

    public function handle(ActionRequest $request, Person $person): Person
    {
        $filePath = Storage::putFile('profile-photos', $request->file('profile_photo'));
        $pathParts = explode('/', $filePath);

        $filename = array_pop($pathParts);
        $person->update(['profile_photo' => $filename]);

        event(new ProfileUpdated($person, ['profile_photo']));

        return $person;
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage') || $request->user()->isLinkedToPerson($request->person);
    }

    public function rules(): array
    {
        return [
           'profile_photo' => 'required|file|max:2000|mimes:jpeg,jpg,gif,png',
        ];
    }

}
