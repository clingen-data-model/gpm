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
            defaultSelect: [
                'id', 
                'comment_type_id', 
                'creator_id', 
                'creator_type', 
                'resolved_at', 
                'content', 
                'metadata'
            ],
            defaultWith: [
                'type' => function ($q) {
                    return $q->select(['id', 'name', 'description']);
                },
                'creator' => function ($q) {
                    return $q->select('id', 'first_name', 'last_name', 'email');
                }
            ]
        );

        $query = $search->buildQuery($queryParams)->withCount('comments');

        return $query->get();
    }

    public function asController(ActionRequest $request)
    {
        return $this->handle($request->all());
    }
}