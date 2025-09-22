<?php 

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Publication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'group_id',
        'added_by_id',
        'updated_by_id',
        'source',
        'identifier',
        'meta',
        'pub_type',
        'published_at',
        'status',
        'error',
    ];

    protected $casts = [
        'meta' => 'array',
        'published_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
        });
    }

    public function group()
    {
        return $this->belongsTo(\App\Modules\Group\Models\Group::class);
    }
}
