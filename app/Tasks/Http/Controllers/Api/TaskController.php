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
            modelClass: Task::class,
            whereFunction: function ($query, $where) {
                foreach ($where as $key => $value) {
                    if ($key == 'pending') {
                        $query->pending();
                        continue;
                    }
                    if ($key == 'completed') {
                        $query->completed();
                        continue;
                    }
                    if (is_array($value)) {
                        $query->whereIn($key, $value);
                    } else {
                        $query->where($key, $value);
                    }
                }
                return $query;
            }
        );

        return $searchService->search($request->all());
    }
}
