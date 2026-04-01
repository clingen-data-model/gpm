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
        if (!$group->isEp) {
            throw new ModelNotFoundException('Only expert panels have gene lists.');
        }

        $query = $group
                ->expertPanel
                ->genes()
                ->select('gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name', 'id')
                ;
        if ($request->with) {
            $query->with($request->with);
        }
        return $query->get();
    }
}
