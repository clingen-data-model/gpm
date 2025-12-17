<?php

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
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->string('clinvar_org_id', 20)
                ->nullable()
                ->after('affiliation_id')
                ->comment('ClinVar ID (used only for VCEP groups)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->dropColumn('clinvar_org_id');
        });
    }
};