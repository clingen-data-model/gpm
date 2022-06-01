<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NextActionType extends Model
{
    use HasFactory;

    public $fillable = [
        'name', 
        'description',
        'default_entry'
    ];

    public $casts = [
        'id' => 'integer'
    ];
    
    
}
