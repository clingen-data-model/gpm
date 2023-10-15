<?php

use App\Modules\ExpertPanel\Models\Coi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('cois', 'group_id')) {
            Schema::table('cois', function (Blueprint $table) {
                $table->unsignedBigInteger('group_id')->after('group_member_id');
            });
        }

        $this->replaceExpertPanelIdWithGroupId();

        Schema::table('cois', function (Blueprint $table) {
            if (\DB::connection()->getDriverName() != 'sqlite') {
                $table->dropForeign('cois_v2_expert_panel_id_foreign');
            }
            $table->dropColumn('expert_panel_id');
            $table->foreign('group_id')->references('id')->on('groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->unsignedBigInteger('expert_panel_id');
        });

        $this->replaceGroupIdWithExpertPanelId();

        Schema::table('cois', function (Blueprint $table) {
            if (\DB::connection()->getDriverName() == 'mysql') {
                $table->dropForeign(['group_id']);
            }
            $table->dropColumn('group_id');
            $table->foreign('expert_panel_id')->references('id')->on('expert_panels');
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
};
