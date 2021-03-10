<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('cois', function (Blueprint $table) {
            $coiDefinition = json_decode(file_get_contents(base_path('resources/surveys/coi.json')));
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->json('data');
            $table->timestamps();

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
        Schema::dropIfExists('cois');
    }
}
