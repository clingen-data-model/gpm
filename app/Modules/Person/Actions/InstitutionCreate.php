<?php

namespace App\Modules\Person\Actions;

use Ramsey\Uuid\Uuid;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\Concerns\AsController;

class InstitutionCreate
{
    use AsController;

    public function handle(
        string $name,
        string $city,
        int $country_id,
        ?string $abbreviation = null,
        ?string $url = null,
        ?string $address = null,
        ?bool $reportable = true,
    ): Institution {
        return Institution::create([
            'uuid' => Uuid::uuid4(),
            'name' => $name,
            'abbreviation' => $abbreviation,
            'url' => $url,
            'address' => $address,
            'city' => $city,
            'country_id' => $country_id,
            'reportable' => $reportable,
        ]);
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle(
            ...$request->only([
                'name',
                'city',
                'country_id',
                'abbreviation',
                'url',
                'address',
                'reportable',
            ])
        );
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:256', 'unique:institutions,name'],
            'city' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'abbreviation' => ['nullable', 'string', 'max:32'],
            'url' => ['nullable', 'unique:institutions,url'],
            'address' => ['nullable', 'string', 'max:256'],
            'reportable' => ['nullable', 'boolean'],
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'required' => 'This is required.',
            'exists' => 'The selection is invalid.',
        ];
    }
}