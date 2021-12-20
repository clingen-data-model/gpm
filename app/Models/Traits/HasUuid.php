<?php

namespace App\Models\Traits;

/**
 * Adds functions to support models that have a uuid.
 */
trait HasUuid
{
    // Queries
    public function scopeHasUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }
    

    public static function findByUuid($uuid, $columns = '*')
    {
        return static::where('uuid', $uuid)->first($columns);
    }

    public static function findByUuidOrFail($uuid, $columns = '*')
    {
        $model = static::where('uuid', $uuid)->sole($columns);

        return $model;
    }
}
