<?php

namespace App\Models\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Get all of the comments for the HasComments
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'subject');
    }
}