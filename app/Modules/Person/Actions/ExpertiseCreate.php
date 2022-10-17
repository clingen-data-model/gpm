<?php

namespace App\Modules\Person\Actions;

use App\Models\Expertise;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ExpertiseCreate
{
    use AsController;

    public function handle(
        string $name,
        ?bool $approved = false,
        ?array $synonyms = null
    ): Expertise
    {
        return Expertise::create([
            'name' => $name,
            'approved' => $approved,
        ])->load('synonyms');
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle(...$request->only('name', 'approved'))->loadCount('people');
    }

    public function rules(): array
    {
        return [
           'name' => ['required','unique:expertises,name', 'max:255'],
           'approved' => 'nullable|boolean',
        ];
    }
}
