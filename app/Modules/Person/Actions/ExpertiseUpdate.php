<?php

namespace App\Modules\Person\Actions;

use App\Models\Expertise;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ExpertiseUpdate
{
    use AsController;

    public function handle(Expertise $expertise, array $data): Expertise
    {
        $expertise->update($data);

        return $expertise;
    }

    public function asController(ActionRequest $request, Expertise $expertise)
    {
        return $this->handle($expertise, $request->safe(['name', 'approved']))->loadCount('people');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:255', Rule::unique('expertises', 'name')->ignore(request()->expertise->id)],
            'approved' => 'nullable|boolean'
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }

}
