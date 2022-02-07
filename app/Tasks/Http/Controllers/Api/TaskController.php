<?php

namespace App\Tasks\Http\Controllers\Api;

use App\Tasks\Models\Task;
use App\ModelSearchService;
use Illuminate\Http\Request;

class TaskController
{
    public function index(Request $request)
    {
        $searchService = new ModelSearchService(
            modelClass: Task::class
        );

        return $searchService->search($request->all());
    }
}
