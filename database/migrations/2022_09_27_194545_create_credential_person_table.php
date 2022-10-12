<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCredentialPersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credential_person', function (Blueprint $table) {
            $table->unsignedBigInteger('credential_id');
            $table->unsignedBigInteger('person_id');
            $table->timestamps();

            $table->primary(['credential_id', 'person_id']);

            $table->foreign('credential_id')->references('id')->on('credentials')->onDelete('cascade');
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credential_person');
    }
}
