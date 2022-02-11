<?php

namespace App\Models\GeneTracker;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gene extends Model
{
    use HasFactory;

    protected $table = 'genes';
    protected $primaryKey = 'hgnc_id';
    // protected $connection = 'genetracker';

    public function getConnectionName()
    {
        return config('database.gt_db_connection');
    }
}
