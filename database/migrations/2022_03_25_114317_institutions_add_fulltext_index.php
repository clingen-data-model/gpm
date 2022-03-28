<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InstitutionsAddFulltextIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('institutions', function (Blueprint $table) {
                $table->fullText(['name', 'abbreviation'], 'search_fulltext');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
                Schema::table('institutions', function (Blueprint $table) {
                $table->dropFullText('search_fulltext');
            });
        }
    }
}
