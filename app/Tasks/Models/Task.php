<?php

namespace App\Tasks\Models;

use App\Models\TaskType;
use App\Tasks\Contracts\TaskAssignee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $fillable = [
        'task_type_id',
        'completed_at',
        'assignee_type',
        'assignee_id'
    ];

    public $casts = [
        'id' => 'integer',
        'completed_at' => 'datetime',
        'assignee_id' => 'integer'
    ];

    /**
     * RELATIONS
     */

    public function assignee()
    {
        return $this->morphTo();
    }
    
    public function type()
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    /**
     * SCOPES
     */

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeHasType($query, $type)
    {
        if (is_string($type)) {
            if (!array_key_exists($type, config('tasks.types'))) {
                throw new \Exception('unknown task type '.$type);
            }
            return $query->where('task_type_id', config('tasks.types.'.$type.'.id'));
        }

        if (is_object($type) && get_class($type) == TaskType::class) {
            return $query->where('task_type_id', $type->id);
        }

        return $query->where('task_type_id', $type);
    }

    /**
     * Setters
     */
    
    public function for(TaskAssignee $assignee)
    {
        $this->assignee_type = get_class($assignee);
        $this->assignee_id = $assignee->id;

        return $this;
    }
}
