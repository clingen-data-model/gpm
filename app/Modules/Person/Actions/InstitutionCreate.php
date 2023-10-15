<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Ramsey\Uuid\Uuid;

class InstitutionCreate
{
    use AsController;

    public function handle(
        string $name,
        string $abbreviation = null,
        string $url = null,
        string $address = null,
        int $country_id = null,
        ?bool $reportable = true,
        // ?int $website_id = null,
    ): Institution {
        return Institution::create([
            'uuid' => Uuid::uuid4(),
            'name' => $name,
            'abbreviation' => $abbreviation,
            'url' => $url,
            'address' => $address,
            'country_id' => $country_id,
            'reportable' => $reportable,
            // 'website_id' => $website_id
        ]);
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle(...$request->only('name', 'abbreviation', 'url', 'address', 'country_id', 'reportable', 'website_id'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|max:256|unique:institutions,name',
            'abbreviation' => 'nullable|max:32',
            'url' => 'nullable|unique:institutions,url',
            'address' => 'nullable|max:256',
            'country_id' => 'nullable|exists:countries,id',
            'reportable' => 'nullable|boolean',
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
