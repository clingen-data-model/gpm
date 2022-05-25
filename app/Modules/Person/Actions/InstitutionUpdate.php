<?php
namespace App\Modules\Person\Actions;

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
        $data = $request->only(['name', 'abbreviation', 'url', 'country_id', 'address', 'reportable']);
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
        return [
           'name' => 'required|max:255',
           'abbreviation' => 'nullable|max:255',
           'url' => 'nullable|max:255',
           'country_id' => 'nullable|exists:countries,id',
           'address' => 'nullable|max:255',
           'reportable' => 'nullable|boolean',
        ];
    }
}
