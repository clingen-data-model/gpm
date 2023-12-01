<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class InstitutionDelete
{
    use AsController;

    public function handle(Institution $institution): void
    {
        $institution->delete();
    }

    public function asController(ActionRequest $request, Institution $institution)
    {
        return $this->handle($institution);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }
}
