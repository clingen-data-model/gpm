<?php

namespace App\Models\GeneTracker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    protected $table = 'diseases';
    protected $primaryKey = 'hgnc_id';

    public function getConnectionName()
    {
        return config('database.gt_db_connection');
    }
}
