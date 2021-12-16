<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
            $table->unsignedBigInteger('submission_type_id');
            $table->foreign('submission_type_id')->references('id')->on('submission_types')->cascadeOnDelete();
            $table->unsignedBigInteger('submission_status_id')->default(1);
            $table->foreign('submission_status_id')->references('id')->on('submission_statuses')->cascadeOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('submitter_id');
            $table->foreign('submitter_id')->references('id')->on('people')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submissions');
    }
}
