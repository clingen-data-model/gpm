<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommentType extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description'
    ];

    public $casts = [
        'id' => 'integer'
    ];

    /**
     * Get all of the comments for the CommentType
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
