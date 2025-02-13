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

        return $this->getDb()->table('genes')->get();
    }

    private function seedDiseases($diseases = null)
    {
        $diseases = $diseases ?? ['mondo_id' => 'MONDO:9876543', 'name'=>'gladiola syndrome'];
        $this->getDb()->table('diseases')
            ->insert($diseases);
            
        return $this->getDb()->table('diseases')->get();
    }

    private function getDb()
    {
        $conn = DB::connection(config('database.gt_db_connection'));
        // This is an ugly hack for the fact that RefreshDatabase doesn't work with multiple connections
        // The real fix would be to access genetracker only through an API (and to mock it appropriately)
        if (!$conn->getSchemaBuilder()->hasTable('genes')) {
            $conn->getSchemaBuilder()->create('genes', function($table){
                $table->biginteger('hgnc_id');
                $table->string('gene_symbol');
            });
        }
        if (!$conn->getSchemaBuilder()->hasTable('diseases')) {
            $conn->getSchemaBuilder()->create('diseases', function($table){
                $table->bigIncrements('id');
                $table->string('mondo_id');
                $table->string('name');
            });
        }
        return $conn;
    }
    
    
    
}