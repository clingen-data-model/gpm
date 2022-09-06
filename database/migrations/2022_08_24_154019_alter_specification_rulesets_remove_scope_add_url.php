<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSpecificationRulesetsRemoveScopeAddUrl extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('specification_rulesets', function (Blueprint $table) {
            $table->dropColumn('gene_symbol');
            $table->string('url');
        });

        Schema::table('specification_rulesets', function (Blueprint $table) {
            $table->dropColumn('disease_name');
        });

        Schema::table('specification_rulesets', function (Blueprint $table) {
            $table->dropColumn('mondo_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('specification_rulesets', function (Blueprint $table) {
            $table->string('gene_symbol');
            $table->string('mondo_id', 13);
            $table->string('disease_name');

            $table->dropColumn('url');
        });
    }
}
