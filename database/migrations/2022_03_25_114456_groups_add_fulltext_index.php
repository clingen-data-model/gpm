<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GroupsAddFulltextIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('groups', function (Blueprint $table) {
                $table->fullText(['name'], 'search_fulltext');
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
            Schema::table('groups', function (Blueprint $table) {
                $table->dropFullText('search_fulltext');
            });
        }
    }
}
