<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpertisePersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expertise_person', function (Blueprint $table) {
            $table->unsignedBigInteger('expertise_id');
            $table->unsignedBigInteger('person_id');

            $table->primary(['expertise_id', 'person_id']);

            $table->foreign('expertise_id')->references('id')->on('expertises')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');

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
        Schema::dropIfExists('expertise_person');
    }
}
