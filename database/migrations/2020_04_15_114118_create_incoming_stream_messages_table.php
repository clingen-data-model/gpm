<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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
            $table->dateTime('processed_at')->nullable();

            $table->index('key');
            $table->index('timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_stream_messages');
    }
};
