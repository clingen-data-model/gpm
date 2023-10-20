<?php

namespace App\Models\GeneTracker;

use App\Modules\ExpertPanel\Models\Gene as GpmGene;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gene extends Model
{
    use HasFactory;

    protected $primaryKey = 'hgnc_id';
    // protected $connection = 'genetracker';

    public function getConnectionName()
    {
        return config('database.gt_db_connection');
    }

    /**
     * Get all of the scopedGenes for the Gene
     */
    public function gpmGenes(): HasMany
    {
        return $this->hasMany(GpmGene::class, 'foreign_key', 'local_key');
    }
}
