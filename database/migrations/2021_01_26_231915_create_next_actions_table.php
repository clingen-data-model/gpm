<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNextActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('next_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->text('entry');
            $table->date('date_created');
            $table->date('target_date')->nullable();
            $table->date('date_completed')->nullable();
            $table->integer('step')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_id')->references('id')->on('applications');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('next_actions');
    }
}
