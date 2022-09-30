<?php

namespace App\Modules\Person\Actions;

use App\Models\Expertise;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Person\Actions\ExpertiseDelete;
use Illuminate\Support\Facades\DB;

class ExpertisesMerge
{
    use AsController;

    public function __construct(private ExpertiseDelete $deleteExpertise)
    {
    }


    public function handle(Expertise $obsolete, Expertise $authority): Expertise
    {
        DB::transaction(function () use ($obsolete, $authority) {
            $this->transferPeople($obsolete, $authority);
            $this->deleteExpertise->handle($obsolete);
        });

        return $authority->loadCount('people');
    }

    public function asController(ActionRequest $request)
    {
        $obsolete = Expertise::findOrFail($request->obsolete_id);
        $authority = Expertise::findOrFail($request->authority_id);

        return $this->handle($obsolete, $authority);
    }

    public function rules(): array
    {
        return [
           'obsolete_id' => 'required|numeric|exists:expertises,id|different:authority_id',
           'authority_id' => 'required|numeric|exists:expertises,id|different:obsolete_id',
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }


    private function transferPeople(Expertise $obsolete, Expertise $authority): void
    {
        $obsolete->people
            ->each(function ($person) use ($authority, $obsolete) {
                $person->expertises()->detach($obsolete->id);
                $person->expertises()->syncWithoutDetaching([$authority->id]);
            });

    }

}
