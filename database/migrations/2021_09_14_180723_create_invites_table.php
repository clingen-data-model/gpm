<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->nullableMorphs('inviter');
            // $table->foreignId('group_id')
            //     ->constrained()
            //     ->cascadeOnDelete()
            //     ->cascadeOnUpdate();
            $table->foreignId('person_id')
                ->constrained()
                ->cascadeOnDelete()
                ->casacadeOnUpdate();
            $table->dateTime('redeemed_at')->nullable();
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
        Schema::dropIfExists('invites');
    }
}
