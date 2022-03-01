<?php
namespace App\Modules\Person\Actions;

use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Institution;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsController;

class InstitutionsMerge
{
    use AsController;

    public function __construct(private ProfileUpdate $profileUpdate)
    {
        //code
    }
    

    public function handle(Institution $authority, Collection $obsoletes)
    {
        $this->transferPeople($authority, $obsoletes);
        $obsoletes->each->delete();

        return $authority->loadCount('people');
    }

    public function asController(ActionRequest $request)
    {
        $authority = Institution::findOrFail($request->authority_id);
        $obsoletes = Institution::findOrFail($request->obsolete_ids);

        return $this->handle($authority, $obsoletes);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }

    public function rules(): array
    {
        return [
           'authority_id' => 'required|exists:institutions,id',
           'obsolete_ids' => 'required',
           'obsolete_ids.*' => 'exists:institutions,id'
        ];
    }

    private function transferPeople(Institution $authority, Collection $obsoletes)
    {
        $obsoletes->load('people');
        $obsoletes->pluck('people')
            ->flatten()
            ->each(function ($person) use ($authority) {
                $this->profileUpdate->handle($person, ['institution_id' => $authority->id]);
            });
    }
}
