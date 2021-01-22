<?php

namespace App\Domain\Application\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone'
    ];

    // Queries
    static public function findByEmail($email)
    {
        return static::where('email', $email)->first();
    }

    static protected function newFactory()
    {
        return new PersonFactory();
    }
    
}
