<?php

namespace App\Models\GeneTracker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Modules\ExpertPanel\Models\Gene as GpmGene;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * Get all of the scopedGenes for the Gene
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gpmGenes(): HasMany
    {
        return $this->hasMany(GpmGene::class, 'foreign_key', 'local_key');
    }
}
