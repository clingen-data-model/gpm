<?php

use App\Actions\DataMigration;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateApplicationDataToExpertPanels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DataMigration::run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('expert_panels')->delete();
    }
}
