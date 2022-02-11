<?php

namespace App\Models\GeneTracker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    protected $table = 'diseases';
    protected $primaryKey = 'mondo_id';
    protected $connection = 'genetracker';
}
