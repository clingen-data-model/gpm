<?php

namespace App\Modules\Person\Actions;

use App\Models\Credential;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Person\Actions\CredentialDelete;
use Illuminate\Support\Facades\DB;

class CredentialsMerge
{
    use AsController;

    public function __construct(private CredentialDelete $deleteCredential)
    {
    }


    public function handle(Credential $obsolete, Credential $authority): Credential
    {
        DB::transaction(function () use ($obsolete, $authority) {
            $this->transferPeople($obsolete, $authority);
            $this->deleteCredential->handle($obsolete);
        });

        return $authority->loadCount('people');
    }

    public function asController(ActionRequest $request)
    {
        $obsolete = Credential::findOrFail($request->obsolete_id);
        $authority = Credential::findOrFail($request->authority_id);

        return $this->handle($obsolete, $authority);
    }

    public function rules(): array
    {
        return [
           'obsolete_id' => 'required|numeric|exists:credentials,id|different:authority_id',
           'authority_id' => 'required|numeric|exists:credentials,id|different:obsolete_id',
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('people-manage');
    }


    private function transferPeople(Credential $obsolete, Credential $authority): void
    {
        $obsolete->people
            ->each(function ($person) use ($authority, $obsolete) {
                $person->credentials()->detach($obsolete->id);
                $person->credentials()->syncWithoutDetaching([$authority->id]);
            });

    }

}
