<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupVisibility extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
