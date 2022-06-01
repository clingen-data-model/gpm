<?php

namespace App\Models\Traits;

/**
 * 
 */
trait HasEmail
{
    public function scopeEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    static public function findByEmail($email)
    {
        return static::email($email)->first();
    }

    static public function findByEmailOrFail($email)
    {
        return static::email($email)->sole();
    }

}
