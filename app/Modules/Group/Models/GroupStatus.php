<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\GroupStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GroupStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected static function newFactory()
    {
        return new GroupStatusFactory();
    }
}
