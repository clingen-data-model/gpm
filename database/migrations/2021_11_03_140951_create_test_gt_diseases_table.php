<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestGtDiseasesTable extends Migration
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
            if (!$this->schema->hasTable('diseases')) {
                $this->schema->create('diseases', function ($table) {
                    $table->bigIncrements('id');
                    $table->string('mondo_id');
                    $table->string('name');
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
        $this->schema->dropIfExists('diseases');
    }
}
