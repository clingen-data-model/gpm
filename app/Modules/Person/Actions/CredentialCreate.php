<?php

namespace App\Modules\Person\Actions;

use App\Models\Credential;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CredentialCreate
{
    use AsController;

    public function handle(
        string $name,
        ?bool $approved = false,
        ?array $synonyms = null
    ): Credential
    {
        return Credential::create([
            'name' => $name,
            'approved' => $approved,
        ])->load('synonyms');
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle(...$request->only('name', 'approved'));
    }

    public function rules(): array
    {
        return [
           'name' => ['required','unique:credentials,name', 'max:255'],
           'approved' => 'nullable|boolean',
        ];
    }
}
