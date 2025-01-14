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
        Schema::table('group_types', function (Blueprint $table) {
            $table->enum(
                'curation_product', 
                [
                    'variant_pathogenicity',
                    'gene_disease_validity',
                    'genetic_dosage_sensitivity',
                    'secondary_findings_actionability'
                ])->nullable();
            $table->tinyInteger('is_somatic_cancer')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_types', function (Blueprint $table) {
            $table->dropColumn('curation_product');
            $table->dropColumn('is_somatic_cancer');
        });
    }
};
