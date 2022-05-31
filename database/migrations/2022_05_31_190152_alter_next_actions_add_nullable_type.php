<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNextActionsAddNullableType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('next_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')
                ->nullable();

            $table->foreign('type_id')->references('id')->on('next_action_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('next_actions', function (Blueprint $table) {
            $table->dropIndex(['type_id']);
            $table->dropColumn('type_id');
        });
    }
}
