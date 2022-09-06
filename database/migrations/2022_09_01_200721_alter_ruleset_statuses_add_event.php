<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRulesetStatusesAddEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ruleset_statuses', function (Blueprint $table) {
            $table->string('event')->after('name');
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ruleset_statuses', function (Blueprint $table) {
            $table->dropIndex(['event']);
            $table->dropColumn('event');
        });
    }
}
