<?php

namespace App\Models;

use App\Models\Contracts\HasComments as ContractsHasComments;
use App\Models\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model implements ContractsHasComments
{
    use HasFactory;
    use SoftDeletes;
    use HasComments; 

    public $fillable = [
        'content',
        'comment_type_id',
        'subject_type',
        'subject_id',
        'creator_type',
        'creator_id',
        'metadata',
    ];

    public $casts = [
        'id' => 'integer',
        'subject_id' => 'integer',
        'creator_id' => 'integer',
        'metadata' => 'array'
    ];

    /**
     * Get the type that owns the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CommentTYpe::class, 'comment_type_id');
    }

    public function creator()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
