<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class InstitutionMarkApproved
{
    use AsController;

    public function handle(Institution $institution): Institution
    {
        $institution->update(['approved' => true]);

        return $institution->load('country')
                ->loadCount('people');
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }
}
