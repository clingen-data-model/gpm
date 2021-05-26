<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    use HasFactory;

    static public function boot():void
    {
        parent::boot();

        /*
         * Copy activity_type from properties json column to 
         * activity_type column for indexing, speed of retrieval,
         * and accessor
         */
        static::saving(function ($activity) {
            if ($activity->getExtraProperty('activity_type')) {
                $activity->activity_type = $activity->getExtraProperty('activity_type');
            }
        });
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['activity_type']);
    }

    public function getTypeAttribute()
    {
        return $this->attributes['activity_type'];
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['properties']['activity_type'] = $value;
        $this->attributes['activity_type'] = $value;
    }
    
}
