<?php

namespace App\Modules\Person\Actions;

use App\Models\Expertise;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ExpertiseDelete
{
    use AsController;

    public function handle(Expertise $expertise)
    {
        $expertise->delete();
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }

}
