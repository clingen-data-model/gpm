<?php

namespace App\Modules\Group\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GeneListController extends Controller
{
    public function index(Request $request, Group $group)
    {
        // $group = Group::findByUuidOrFail($groupUuid);
        
        if (!$group->isEp) {
            throw new ModelNotFoundException('Only expert panels have gene lists.');
        }

        return $group
                ->expertPanel
                ->genes()
                ->select('gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name')
                // ->with(['gene', 'disease'])
                ->get();
    }
}
