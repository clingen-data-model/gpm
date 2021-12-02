<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterExpertPanelsAddCurationReviewProcessNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->text('curation_review_process_notes')->nullable();
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
            $table->dropColumn('curation_review_process_notes');
        });
    }
}
