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
        Schema::disableForeignKeyConstraints();

        Schema::create('next_actions_v2', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->foreignId('expert_panel_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->text('entry');
            $table->foreignId('assignee_id')
                ->nullable()
                ->constrained('next_action_assignees')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('assignee_name')->nullable();
            $table->dateTime('date_created');
            $table->dateTime('target_date')->nullable();
            $table->dateTime('date_completed')->nullable();
            $table->integer('application_step')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('next_actions_v2');
    }
};
