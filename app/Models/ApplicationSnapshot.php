<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationSnapshot extends Model
{
    use HasFactory;

    public $fillable = ['group_id', 'version', 'snapshot', 'submission_id'];
    protected $casts = [
        'id' => 'integer',
        'group_id' => 'integer',
        'version' => 'integer',
        'snapshot' => 'array',
    ];

    // RELATIONS

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // SCOPES
    public function scopeForGroup($query, $group)
    {
        $id = $group;
        if (is_object($group)) {
            $id = $group->id;
        }
        return $query->where('group_id', $id);
    }
    
}
