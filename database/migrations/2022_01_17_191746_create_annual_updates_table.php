<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('annual_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expert_panel_id');
            $table->foreign('expert_panel_id')
                ->references('id')->on('expert_panels')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedBigInteger('annual_update_window_id');
            $table->foreign('annual_update_window_id')
                ->references('id')->on('annual_update_windows')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedBigInteger('submitter_id')->nullable();
            $table->foreign('submitter_id')
                ->references('id')->on('group_members')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->dateTime('completed_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_updates');
    }
};
