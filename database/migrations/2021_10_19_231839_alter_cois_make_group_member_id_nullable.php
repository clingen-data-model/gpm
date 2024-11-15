<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCoisMakeGroupMemberIdNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->foreignId('group_member_id')
                ->nullable()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->foreignId('group_member_id')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->change();
        });
    }
}
