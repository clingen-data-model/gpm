<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\NextActionFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\ExpertPanel\Models\NextActionAssignee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;

class NextAction extends Model
{
    use HasFactory;
    use HasTimestamps;
    use SoftDeletes;
    use HasUuid;

    protected $fillable = [
        'entry',
        'date_created',
        'date_completed',
        'target_date',
        'step',
        'application_id',
        'uuid',
        'assigned_to',
        'assigned_to_name'
    ];

    protected $dates = [
        'date_created',
        'date_completed',
        'target_date'
    ];

    protected $with = ['assignee'];

    /**
     * Get the assignedTo that owns the NextAction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(NextActionAssignee::class, 'assigned_to', 'id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('date_completed');
    }

    // Factory
    static protected function newFactory()
    {
        return new NextActionFactory();
    }
}
