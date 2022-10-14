<?php

namespace App\Modules\Person\Actions;

use App\Models\Credential;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CredentialUpdate
{
    use AsController;

    public function handle(Credential $credential, array $data): Credential
    {
        $credential->update($data);

        return $credential;
    }

    public function asController(ActionRequest $request, Credential $credential)
    {
        $credential = $this->handle($credential, $request->safe(['name', 'approved']));

        return $credential->loadCount('people');
    }

    public function rules(): array
    {
        return [
           'name' => ['required', 'max:255', Rule::unique('credentials', 'name')->ignore(request()->credential->id)],
           'approved' => 'nullable|boolean'
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }

}
