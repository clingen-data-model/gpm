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
            if (! $this->schema->hasTable('diseases')) {
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
     */
    public function down(): void
    {
        $this->schema->dropIfExists('diseases');
    }
};
