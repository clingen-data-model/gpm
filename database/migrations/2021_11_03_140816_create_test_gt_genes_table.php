<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function __construct()
    {
        $this->schema = Schema::connection(config('database.gt_db_connection'));
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            if (! $this->schema->hasTable('genes')) {
                $this->schema->create('genes', function ($table) {
                    $table->bigIncrements('hgnc_id');
                    $table->string('gene_symbol');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->schema->dropIfExists('genes');
    }
};
