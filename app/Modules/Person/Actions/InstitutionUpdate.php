<?php
namespace App\Modules\Person\Actions;

use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\Concerns\AsController;

class InstitutionUpdate
{
    use AsController;

    public function handle(Institution $institution, array $data): Institution
    {
        $institution->update($data);
        return $institution;
    }

    public function asController(ActionRequest $request, Institution $institution)
    {
        $data = $request->only(['name', 'abbreviation', 'url', 'city', 'country_id', 'address', 'reportable']);
        return $this->handle($institution, $data)
                ->load('country')
                ->loadCount('people');
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }

    public function rules(): array
    {
        $institution = $request->route('institution');
        return [
            'name'          => ['required', 'string', 'max:255', Rule::unique('institutions', 'name')->ignore($institution?->id)],
            'abbreviation'  => ['nullable', 'string', 'max:255'],
            'url'           => ['nullable', 'string', 'max:255', Rule::unique('institutions', 'url')->ignore($institution?->id)],
            'city'          => ['required', 'string', 'max:255'],
            'country_id'    => ['required', 'integer', 'exists:countries,id'],
            'address'       => ['nullable', 'string', 'max:255'],
            'reportable'    => ['nullable', 'boolean'],
        ];
    }
}
