<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_type_id');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->morphs('subject');
            $table->morphs('creator');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('comment_type_id')->references('id')->on('comment_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
