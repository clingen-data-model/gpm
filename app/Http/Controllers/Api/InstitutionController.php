<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\ModelSearchService;
use App\Modules\Person\Models\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $searchService = new ModelSearchService(
            modelClass: Institution::class,
            defaultWith: ['country']
        );

        $query = $searchService->buildQuery($request->all());

        return $query
                ->withCount('people')
                ->get();
    }
}
