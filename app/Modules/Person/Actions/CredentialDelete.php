<?php

namespace App\Modules\Person\Actions;

use App\Models\Credential;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CredentialDelete
{
    use AsController;

    public function handle(Credential $credential)
    {
        $credential->delete();
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }

}
