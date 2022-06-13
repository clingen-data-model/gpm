<?php

namespace App\Modules\ExpertPanel\Models;

use App\Models\Traits\HasUuid;
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
        'application_step',
        'expert_panel_id',
        'uuid',
        'assignee_id',
        'assignee_name',
        'assigned_to',
        'assigned_to_name',
        'type_id'
    ];

    protected $casts = [
        'id' => 'int',
        'application_step' => 'int',
        'expert_panel_id' => 'int',
        'assignee_id' => 'int',
        'date_created' => 'datetime',
        'date_completed' => 'datetime',
        'target_date' => 'datetime',
        'type_id' => 'int'
    ];

    protected $with = ['assignee'];

    protected $appends = [
        'assigned_to',
        'assigned_to_name',
        'step'
    ];

    /**
     * Get the assignedTo that owns the NextAction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(NextActionAssignee::class, 'assignee_id', 'id');
    }

    /**
     * Get the type that owns the NextAction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(NextActionType::class, 'type_id');
    }

    public function scopePending($query)
    {
        return $query->whereNull('date_completed');
    }

    public function scopeOfType($query, $type)
    {
        $typeId = $type;
        if (is_object($type)) {
            $typeId = $type->id;
        }

        return $query->where('type_id', $typeId);
    }
    

    /**
     * ACCESSORS
     */

    public function getAssignedToAttribute()
    {
        return $this->assignee_id;
    }

    public function setAssignedToAttribute($value)
    {
        $this->attributes['assignee_id'] = $value;
    }
    
     
    public function getAssignedToNameAttribute()
    {
        return $this->attributes['assignee_name'];
    }

    public function setAssignedToNameAttribute($value)
    {
        return $this->attributes['assignee_name'] = $value;
    }
    

    public function getStepAttribute()
    {
        return $this->application_step;
    }
    
    public function setStepAttribute($value)
    {
        $this->attributes['application_step'] = $value;
    }
         

    // Factory
    protected static function newFactory()
    {
        return new NextActionFactory();
    }
}
