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

    // Accessors
    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }
    

    // Factory
    static protected function newFactory()
    {
        return new PersonFactory();
    }
    
}
