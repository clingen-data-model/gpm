<?php

namespace App\Actions;

use App\Http\Resources\ClingenPersonResource;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsController;

class ClingenPersonFind
{
    use AsController;

    public function handle(Person $person): ClingenPersonResource
    {
        $person->load([
            'institution',
            'credentials',
            'memberships' => fn ($query) => $query->whereHas('group')->orderBy('id'),
            'memberships.group.type',
            'memberships.group.expertPanel',
            'memberships.roles' => fn ($query) => $query->orderBy('name'),
        ]);

        return new ClingenPersonResource($person);
    }
}
