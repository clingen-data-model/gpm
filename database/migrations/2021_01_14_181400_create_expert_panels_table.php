<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpertPanelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expert_panels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('short_base_name')->unique();
            $table->string('long_base_name')->unique();
            $table->unsignedBigInteger('cdwg_id');
            $table->string('affiliaton_id', 8)->unique();
            $table->foreign('cdwg_id')->references('id')->on('cdwgs');

            $table->unsignedBigInteger('ep_type_id');
            $table->foreign('ep_type_id')->references('id')->on('ep_types');

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
        Schema::dropIfExists('expert_panels');
    }
}
