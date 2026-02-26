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
            $table->char('assertion_id', 255)->nullable()->after('gene_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evidence_summaries', function (Blueprint $table) {
            $table->dropColumn('assertion_id');
        });
    }
};
