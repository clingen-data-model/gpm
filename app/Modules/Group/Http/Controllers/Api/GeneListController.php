<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class GeneListController extends Controller
{
    public function index(Request $request, Group $group)
    {
        // $group = Group::findByUuidOrFail($groupUuid);

        if (! $group->isEp) {
            throw new ModelNotFoundException('Only expert panels have gene lists.');
        }

        $query = $group
                ->expertPanel
                ->genes()
                ->select('gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name', 'id');
        if ($request->with) {
            $query->with($request->with);
        }

        return $query->get();
    }
}
