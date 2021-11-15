<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateExpertPanelPolymorphicTypeAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // DB::table('activity_log')
        //     ->where('subject_type', 'App\Modules\Application\Models\Application')
        //     ->update(['subject_type' =>'App\Modules\ExpertPanel\Models\ExpertPanel']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // DB::table('activity_log')
        //     ->where('subject_type', 'App\Modules\Application\Models\ExpertPanel')
        //     ->update(['subject_type' =>'App\Modules\Application\Models\Application']);
    }
}
