<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as ModelsPermission;

class Permission extends ModelsPermission
{
    use HasFactory;

    /**
     * SCOPES
     */
    public function scopeGroup($query)
    {
        return $query->where('scope', 'group');
    }

    public function scopeSystem($query)
    {
        return $query->where('scope', 'system');
    }
}
