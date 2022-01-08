<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIncomingStreamMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_stream_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('topic');
            $table->string('key')->nullable()->unique();
            $table->integer('partition');
            $table->integer('offset');
            $table->bigInteger('timestamp')->nullable();
            $table->integer('error_code');
            $table->json('payload')->nullable();
            $table->uuid('gdm_uuid')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_stream_messages');
    }
}
