<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PeopleAddFulltextIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->index(['institution_id'], 'inst_id_idx');
            $table->fullText(['first_name', 'last_name'], 'fulltext_name');
            $table->fullText(['email'], 'fulltext_email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex('inst_id_idx');
            $table->dropFullText('fulltext_name');
            $table->dropFullText('fulltext_email');
        });
    }
}
