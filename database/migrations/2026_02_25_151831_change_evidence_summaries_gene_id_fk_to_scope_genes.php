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
        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->dropForeign('evidence_summaries_gene_id_foreign');
        });

        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->foreign('gene_id')
                ->references('id')
                ->on('scope_genes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->dropForeign(['gene_id']);
        });

        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->foreign('gene_id')
                ->references('id')
                ->on('genes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
