<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_class',
        'follower',
        'completed_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'completed_at' => 'datetime'
    ];

    // SCOPES
    public function scopeForEvent($query, $eventClass)
    {
        return $query->where('event_class', $eventClass);
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }
    
    

    // ACCESSORS
    public function getFollowerAttribute()
    {
        return unserialize($this->attributes['follower']);
    }

    public function setFollowerAttribute(Object $value)
    {
        return $this->attributes['follower'] = serialize($value);
    }
    
}
