<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('follow_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('event_class');
            $table->text('follower');
            $table->json('args')->nullable();
            $table->text('description')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->string('hash')->unique();
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
        Schema::dropIfExists('follow_actions');
    }
}
