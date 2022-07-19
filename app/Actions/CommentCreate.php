<?php

namespace App\Actions;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentCreate
{
    	use AsController;

    public function handle(array $data)
    {
        $data['creator_type'] = get_class(Auth::user()->person);
        $data['creator_id'] = Auth::user()->person->id;
        return Comment::create($data);
    }

    public function asController(ActionRequest $request)
    {
        $comment = $this->handle($request->all());
        $comment->load(['creator', 'type']);
        return $comment;
    }

    public function rules(ActionRequest $request): array
    {
        return [
            'content' => 'required',
            'subject_type' => 'required',
            'subject_id' => 'required',
            'comment_type_id' => 'required|exists:comment_types,id',
            'metadata' => 'nullable|array'
        ];
    }

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasPermissionTo('ep-applications-comment');
    }

}