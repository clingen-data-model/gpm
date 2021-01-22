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
            $table->unsignedBigInteger('ep_type_id');
            $table->integer('current_step')->default(1);
            $table->unsignedBigInteger('expert_panel_id')->nullable();
            $table->unsignedBigInteger('cdwg_id')->nullable();
            $table->string('survey_monkey_url')->unique()->nullable(); // can be nullable b/c we're going to asyncronously get a new data collector from SurveyMonkey
            $table->date('date_initiated')->nullable();
            $table->timestamps();
            
            $table->foreign('ep_type_id')->references('id')->on('ep_types');
            $table->foreign('expert_panel_id')->references('id')->on('expert_panels');
            $table->foreign('cdwg_id')->references('id')->on('cdwgs');
            
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
