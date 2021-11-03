<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait SeedsHgncGenesAndDiseases
{
    private function seedGenes($genes = null)
    {
        $genes = $genes ?? ['hgnc_id' => 12345, 'gene_symbol'=>'ABC1'];
        $this->getDb()->table('genes')
            ->insert($genes);
        
    }

    private function seedDiseases($diseases = null)
    {
        $diseases = $diseases ?? ['mondo_id' => 'MONDO:9876543', 'name'=>'gladiola syndrome'];
        $this->getDb()->table('diseases')
            ->insert($diseases);
    }

    private function getDb()
    {
        return DB::connection(config('database.gt_db_connection'));
    }
    
    
    
}