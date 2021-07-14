<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNextActionsAddAssignedToAndAssignedToName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('next_actions', function (Blueprint $table) {
            if (!Schema::hasColumn('next_actions', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')
                    ->nullable()
                    ->default(2)
                    ->after('target_date');
                $table->index(['assigned_to'], 'assigned_to_index');
                $table->foreign('assigned_to')->references('id')->on('next_action_assignees');
            }

            if (!Schema::hasColumn('next_actions', 'assigned_to_name')) {
                $table->string('assigned_to_name')
                    ->nullable()
                    ->after('assigned_to');
            }
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
            $table->dropColumn('assigned_to');
            $table->dropColumn('assigned_to_name');
        });
    }
}
