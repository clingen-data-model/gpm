<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->validateExistingAffiliationIds();

        Schema::table('groups', function (Blueprint $table) {
            $table->string('affiliation_id', 8)->unique()->nullable()->after('uuid');
        });
        DB::statement('UPDATE `groups` INNER JOIN expert_panels ep ON ep.group_id = `groups`.id SET `groups`.affiliation_id = CAST(ep.affiliation_id AS CHAR) WHERE ep.affiliation_id IS NOT NULL');
        Schema::table('expert_panels', function (Blueprint $table) { $table->dropColumn('affiliation_id'); });
    }
    
    public function down(): void
    {
        $nonExpertPanelIds = DB::table('groups')
            ->leftJoin('expert_panels as ep', 'ep.group_id', '=', 'groups.id')
            ->whereNotNull('groups.affiliation_id')
            ->whereNull('ep.id')
            ->count();

        if ($nonExpertPanelIds > 0) {
            throw new RuntimeException('Cannot roll back because non-EP groups have Affiliation IDs.');
        }

        Schema::table('expert_panels', function (Blueprint $table) {
            $table->string('affiliation_id', 8)->unique()->nullable();
        });

        DB::statement('UPDATE expert_panels ep INNER JOIN `groups` ON `groups`.id = ep.group_id SET ep.affiliation_id = `groups`.affiliation_id WHERE `groups`.affiliation_id IS NOT NULL');
        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique(['affiliation_id']);
            $table->dropColumn('affiliation_id');
        });
    }

    private function validateExistingAffiliationIds(): void
    {
        $invalidIds = DB::table('expert_panels')->whereNotNull('affiliation_id')->whereRaw("CAST(affiliation_id AS CHAR) NOT REGEXP '^[0-9]{5}$'")->pluck('affiliation_id');
        if ($invalidIds->isNotEmpty()) {
            throw new RuntimeException('Invalid Affiliation IDs found: '.$invalidIds->implode(', '));
        }
        $duplicateIds = DB::table('expert_panels')->select('affiliation_id')->whereNotNull('affiliation_id')->groupBy('affiliation_id')->havingRaw('COUNT(*) > 1')->pluck('affiliation_id');

        if ($duplicateIds->isNotEmpty()) {
            throw new RuntimeException('Duplicate Affiliation IDs found: '.$duplicateIds->implode(', '));
        }

        $duplicateGroupIds = DB::table('expert_panels')->select('group_id')->whereNotNull('affiliation_id')->groupBy('group_id')->havingRaw('COUNT(*) > 1')->pluck('group_id');
        if ($duplicateGroupIds->isNotEmpty()) {
            throw new RuntimeException('Multiple Expert Panels with Affiliation IDs reference these group IDs: '.$duplicateGroupIds->implode(', '));
        }

        $missingGroupIds = DB::table('expert_panels as ep')->leftJoin('groups', 'groups.id', '=', 'ep.group_id')->whereNotNull('ep.affiliation_id')->whereNull('groups.id')->pluck('ep.group_id');
        if ($missingGroupIds->isNotEmpty()) {
            throw new RuntimeException('Expert Panels reference missing group IDs: '.$missingGroupIds->implode(', '));
        }
    }
};
