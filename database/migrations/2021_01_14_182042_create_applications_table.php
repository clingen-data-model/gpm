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
            $table->unsignedBigInteger('cdwg_id')->nullable();
            $table->string('short_base_name')->unique()->nullable();
            $table->string('long_base_name')->unique()->nullable();
            $table->string('affiliation_id', 8)->unique()->nullable();
            $table->integer('current_step')->default(1);
            $table->date('date_initiated')->nullable();
            $table->json('approval_dates')->nullable();
            $table->date('date_completed')->nullable();
            $table->string('survey_monkey_url')->unique()->nullable(); // can be nullable b/c we're going to asyncronously get a new data collector from SurveyMonkey
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('ep_type_id')->references('id')->on('ep_types');
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
