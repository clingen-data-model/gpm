<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoredEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stored_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('aggregate_id');
            $table->integer('aggregate_version');
            $table->string('type');
            $table->json('data');
            $table->timestamps();

            $table->index('aggregate_id', 'aggregate_index');
            $table->index(['aggregate_id', 'aggregate_version'], 'aggregate_id_version_index');
        });
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stored_events');
    }
}
