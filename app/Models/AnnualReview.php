<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AnnualReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'expert_panel_id',
        'submitter_id',
        'data',
        'completed_at'
    ];

    protected $casts = [
        'id' => 'int',
        'expert_panel_id' => 'int',
        'submitter_id' => 'int',
        'data' => 'array',
        'completed_at' => 'datetime'
    ];

    /**
     * RELATIONS
     */

    /**
     * Get the expertPanel that owns the AnnualReview
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function expertPanel(): BelongsTo
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    /**
     * Get the submitter that owns the AnnualReview
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    public function getMembers()
    {
        return $this->expertPanel->group->members;
    }

    /**
     * SCOPES
     */

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * ACCESSORS
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getForYearAttribute()
    {
        return $this->created_at->year - 1;
    }
}
