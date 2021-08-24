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
    

    public static function findByUuid($uuid)
    {
        return static::where('uuid', $uuid)->first();
    }

    public static function findByUuidOrFail($uuid)
    {
        $model = static::where('uuid', $uuid)->sole();

        return $model;
    }
}
