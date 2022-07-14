<?php

namespace App\Models\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{

    static public function bootHasComments()
    {
        static::softDeleted(function ($model) {
            $model->comments->each->delete();
        });

        static::forceDeleted(function ($model) {
            $model->comments->each()->forceDelete();
        });
    }

    /**
     * Get all of the comments for the HasComments
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'subject');
    }

    public function pendingComments(): MorphMany
    {
        return $this->comments()->pending();
    }
    
    public function resolvedComments(): MorphMany
    {
        return $this->comments()->resolved();
    }
    
}