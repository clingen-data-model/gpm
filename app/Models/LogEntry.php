<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogEntry extends Model
{
    use HasFactory;

    public $fillable = [
        'entry',
        'subject_type',
        'subject_id',
        'log_date',
        'author_type',
        'author_id',
        'metadata'
    ];

    public $casts = [
        'subject_id' => 'integer',
        'author_id' => 'integer',
        'metadata' => 'array',
        'log_date' => 'datetime',
    ];

    /**
     * RELATIONS
     */
    
    /**
     * Get the subject that owns the LogEntry
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the author that owns the LogEntry
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * ACCESSORS
     */

    public function getStepAttribute()
    {
        return isset($this->metadata['step']) ? $this->metadata['step'] : null;
    }
}
