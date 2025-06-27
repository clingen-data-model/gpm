<?php

namespace App\Models;

use App\Exceptions\FollowActionDuplicateException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FollowAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_class',
        'follower',
        'args',
        'completed_at',
        'name',
        'description',
    ];

    protected $casts = [
        'id' => 'integer',
        'args' => 'array',
        'completed_at' => 'datetime'
    ];

    static public function boot () {
        parent::boot();
        static::saving(function ($model) {
            $hash_source = $model->event_class.'-'.$model->follower.'-'.json_encode($model->args).'-'.$model->completed_at;
            $model->hash = md5($hash_source);
            if (self::query()->otherWithHash($model->hash, $model)->count() > 0) {
                \Log::info("FollowAction already exists. Skipping.");
            }
        });
    }

    // SCOPES
    public function scopeForEvent($query, $eventClass)
    {
        return $query->where('event_class', $eventClass);
    }

    public function scopeIncomplete($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOtherWithHash($query, $hash, $current)
    {
        return $query->where('hash', $hash)
            ->where('id', '!=', $current->id);
    }


}
