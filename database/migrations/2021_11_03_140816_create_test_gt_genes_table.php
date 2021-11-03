<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestGtGenesTable extends Migration
{

    public function __construct()
    {
        $this->schema = Schema::connection(config("database.gt_db_connection"));
    }
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (app()->environment('testing')) {
            if (!$this->schema->hasTable('genes')) {
                $this->schema->create('genes', function ($table) {
                    $table->bigIncrements('hgnc_id');
                    $table->string('gene_symbol');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->dropIfExists('genes');
    }
}
