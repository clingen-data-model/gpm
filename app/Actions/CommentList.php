<?php

namespace App\Actions;

use App\Models\Comment;
use App\ModelSearchService;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentList
{
    use AsController;

    public function handle($queryParams)
    {
        $search = new ModelSearchService(
            Comment::class, 
            ['type', 'creator']
        );

        $query = $search->buildQuery($queryParams)->withCount('comments');

        return $query->get();
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle($request->all());
    }
}