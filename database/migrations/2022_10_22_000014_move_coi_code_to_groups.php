<?php

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Schema;
use App\Modules\ExpertPanel\Models\Coi;
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
            $table->string('coi_code', 24)->unique();
        });

        $this->migrateCoiCodeToGroup();

        Schema::table('expert_panels', function(Blueprint $table) {
            $table->dropColumn('coi_code');
        });

        Schema::table('cois', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups');
        });

        $this->replaceExpertPanelIdWithGroupId();

        Schema::table('cois', function (Blueprint $table) {
            // $table->dropForeign(['expert_panel_id']);
            $table->dropColumn('expert_panel_id');
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

        Schema::table('cois', function (Blueprint $table) {
            $table->unsignedBigInteger('expert_panel_id');
            $table->foreign('expert_panel_id')->references('id')->on('expert_panels');
        });

        $this->replaceGroupIdWithExpertPanelId();

        Schema::table('cois', function (Blueprint $table) {
            // $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }

    private function migrateCoiCodeToGroup()
    {
        ExpertPanel::with('group')
            ->get()
            ->each(function ($ep) {
                $ep->group->update(['coi_code' => $ep->coi_code]);
            });

        Group::whereIn('group_type_id', [config('group.types.cdwg'), config('group.types.wg')])
            ->update(['coi_code' => app()->make(CoiCodeMake::class)->handle()]);
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

    private function replaceExpertPanelIdWithGroupId()
    {
        Coi::with('expertPanel')
            ->get()
            ->each(function ($coi) {
                $coi->update(['group_id' => $coi->expertPanel->group_id]);
            });
    }

    private function replaceGroupIdWithExpertPanelId()
    {
        Coi::query()
            ->whereHas('group', function ($q) {
                $q->typeExpertPanel();
            })
            ->with('group')
            ->get()
            ->each(function ($coi) {
                $coi->update(['expert_panel_id' => $coi->group->expert_panel_id]);
            });
    }

}
