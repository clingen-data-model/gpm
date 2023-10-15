<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->text('summary')->after('id');
            $table->string('variant')->after('gene_id');
            $table->string('vci_url')->after('variant')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->dropColumn('summary');
            $table->dropColumn('variant');
            $table->dropColumn('vci_url');
        });
    }
};
