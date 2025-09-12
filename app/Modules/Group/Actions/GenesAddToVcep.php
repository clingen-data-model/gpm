<?php

namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class GenesAddToVcep
{
    use AsAction;
    public function __construct()
    {
    }



    public function handle(Group $group, $gene): Group
    {
        if (!$group->isVcepOrScvcep) {
            throw ValidationException::withMessages(['group' => 'The group is not a VCEP.']);
        }

        $model = new Gene([
                'hgnc_id' => $gene['hgnc_id'],
                'gene_symbol' => $gene['gene_symbol'] ?? null,
                'disease_name' => $gene['disease_name'] ?? null,
                'mondo_id' => $gene['mondo_id'] ?? null,
                'moi' => $gene['moi'] ?? null,
                'date_approved' => $gene['date_approved'] ?? null,
                'plan' => $gene['plan'] ?? null,
            ]);

        $group->expertPanel->genes()->save($model);
        event(new GenesAdded($group, collect($model)));

        return $group;
    }
}
