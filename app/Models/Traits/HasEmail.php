<?php

namespace App\Models\Traits;

trait HasEmail
{
    public function scopeEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public static function findByEmail($email)
    {
        return static::email($email)->first();
    }

    public static function findByEmailOrFail($email)
    {
        return static::email($email)->sole();
    }
}
