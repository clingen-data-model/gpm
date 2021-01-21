<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('working_name');
            $table->foreign('ep_type_id')->references('id')->on('ep_types');
            $table->integer('current_step');
            $table->unsignedBigInteger('expert_panel_id');
            $table->foreign('expert_panel_id')->references('id')->on('expert_panels');
            $table->unsignedBigInteger('cdwg_id');
            $table->foreign('cdwg_id')->references('id')->on('cdwgs');
            $table->unsignedBigInteger('ep_type_id');
            $table->string('survey_monkey_url')->unique();
            $table->date('date_initiated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applications');
    }
}
