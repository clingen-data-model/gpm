<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NextActionAssignee extends Model
{
    use HasFactory;

    public $fillable = ['name'];

    public static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            Cache::forget('next-action-assignees');
            Cache::forever('next-action-assignees', static::all());
        });
    }

    public static function getAll()
    {
        return Cache::rememberForever('next-action-assignees', function () {
            return static::all();
        });
    }
}
