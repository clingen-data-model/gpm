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
        String $name,
        ?String $abbreviation = null,
        ?String $url = null,
        ?String $address = null,
        ?int $country_id = null,
        // ?int $website_id = null,
    ): Institution {
        return Institution::create([
            'uuid' => Uuid::uuid4(),
            'name' => $name,
            'abbreviation' => $abbreviation,
            'url' => $url,
            'address' => $address,
            'country_id' => $country_id,
            // 'website_id' => $website_id
        ]);
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle(...$request->only('name', 'abbreviation', 'url', 'address', 'country_id', 'website_id'));
    }

    public function rules(): array
    {
        return [
           'name' => 'required|max:256|unique:institutions,name',
           'abbreviation' => 'nullable|max:32',
           'url' => 'nullable|unique:institutions,url',
           'address' => 'nullable|max:256',
           'country_id' => 'nullable|exists:countries,id',
        //    'website_id' => 'nullable|unique:institutions,website_id',
        ];
    }

    public function getValidationMessages()
    {
        return [
            'required' => 'This is required.',
            'exists' => 'The selection is invalid.',
        ];
    }
}
