<?php

namespace App\Actions;

use App\Models\CommentType;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CommentTypeList
{
    use AsController;

    public function handle()
    {
        return CommentType::all();
    }
}