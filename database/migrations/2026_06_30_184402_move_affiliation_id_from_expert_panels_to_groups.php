<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->validateExistingAffiliationIds();

        Schema::table('groups', function (Blueprint $table) {
            $table->string('affiliation_id', 8)->unique()->nullable()->after('uuid');
        });
         $this->copyAffiliationIdsToGroups();
        Schema::table('expert_panels', function (Blueprint $table) { $table->dropColumn('affiliation_id'); });
    }
    
    public function down(): void
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->string('affiliation_id', 8)->nullable()->unique();
        });        
        DB::statement("UPDATE `expert_panels` AS ep INNER JOIN `groups` AS g ON g.id = ep.group_id SET ep.affiliation_id = g.affiliation_id WHERE g.affiliation_id IS NOT NULL");
        Schema::table('groups', function (Blueprint $table) {
            $table->dropUnique(['affiliation_id']);
            $table->dropColumn('affiliation_id');
        });
    }

    private function copyAffiliationIdsToGroups(): void
    {
        if ($this->isSqlite()) {
            DB::statement('UPDATE "groups" SET affiliation_id = (SELECT ep.affiliation_id FROM expert_panels ep WHERE ep.group_id = "groups".id LIMIT 1) WHERE EXISTS (SELECT 1 FROM expert_panels ep WHERE ep.group_id = "groups".id AND ep.affiliation_id IS NOT NULL)');
            return;
        }
        DB::statement('UPDATE `groups` AS g INNER JOIN expert_panels ep ON ep.group_id = g.id SET g.affiliation_id = CAST(ep.affiliation_id AS CHAR) WHERE ep.affiliation_id IS NOT NULL');
    }

    private function copyAffiliationIdsToExpertPanels(): void
    {
        if ($this->isSqlite()) {
            DB::statement('UPDATE expert_panels SET affiliation_id = (SELECT g.affiliation_id FROM "groups" g WHERE g.id = expert_panels.group_id LIMIT 1) WHERE EXISTS (SELECT 1 FROM "groups" g WHERE g.id = expert_panels.group_id AND g.affiliation_id IS NOT NULL)');
            return;
        }
        DB::statement('UPDATE `expert_panels` AS ep INNER JOIN `groups` AS g ON g.id = ep.group_id SET ep.affiliation_id = g.affiliation_id WHERE g.affiliation_id IS NOT NULL');
    }

    private function validateExistingAffiliationIds(): void
    {
        $invalidIds = DB::table('expert_panels')->whereNotNull('affiliation_id')->pluck('affiliation_id')->filter(fn ($id) => ! preg_match('/^[0-9]{5}$/', (string) $id));
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

        $missingGroupIds = DB::table('expert_panels as ep')->leftJoin('groups as g', 'g.id', '=', 'ep.group_id')->whereNotNull('ep.affiliation_id')->whereNull('g.id')->pluck('ep.group_id');

        if ($missingGroupIds->isNotEmpty()) {
            throw new RuntimeException('Expert Panels reference missing group IDs: '.$missingGroupIds->implode(', '));
        }
    }

    private function isSqlite(): bool
    {
        return Schema::getConnection()->getDriverName() === 'sqlite';
    }
};
