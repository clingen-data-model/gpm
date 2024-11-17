<?php

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Modules\Group\Actions\CoiCodeMake;
use Illuminate\Database\Migrations\Migration;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class MoveCoiCodeToGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('coi_code', 24)->after('parent_id');
        });

        $this->migrateCoiCodeToGroup();

        Schema::table('expert_panels', function(Blueprint $table) {
            $table->dropUnique('expert_panels_coi_code_unique');
            $table->dropColumn('coi_code');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->string('coi_code', 24)->unique();
        });

        $this->migrateCoiCodeToExpertPanel();

        Schema::table('groups', function(Blueprint $table) {
            $table->dropColumn('coi_code');
        });

    }

    private function migrateCoiCodeToGroup()
    {
        ExpertPanel::with('group')
            ->get()
            ->each(function ($ep) {
                $ep->group->update(['coi_code' => $ep->coi_code]);
            });

        Group::whereIn('group_type_id', [config('groups.types.cdwg.id'), config('groups.types.wg.id')])
            ->get()
            ->each(fn ($g) => $g->update(['coi_code' => app()->make(CoiCodeMake::class)->handle()]));
    }

    private function migrateCoiCodeToExpertPanel()
    {
        Group::typeExpertPanel()
            ->with('expertPanel')
            ->get()
            ->each(function ($g) {
                $g->expertPanel->update(['coi_code' => $g->coi_code]);
            });
    }

}
