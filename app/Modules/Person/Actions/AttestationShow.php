<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Attestation;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class AttestationShow
{
    use AsController;
    public function asController(Person $person)
    {
        $attestation = Attestation::query()
            ->where('person_id', $person->id)
            ->whereNull('revoked_at')
            ->whereNull('deleted_at')
            ->first();

        return $attestation;
    }
    
    public function authorize(ActionRequest $request): bool
    {
        $person = $request->route('person');
        return $request->user()?->can('view', $person) ?? false;
    }
}
