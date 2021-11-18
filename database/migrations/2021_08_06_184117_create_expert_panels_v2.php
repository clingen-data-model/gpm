<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpertPanelsV2 extends Migration
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
            $table->foreignId('group_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ;
            $table->foreignId('expert_panel_type_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedBigInteger('cdwg_id')->nullable();
            $table->foreign('cdwg_id')->references('id')->on('groups');

            $table->string('short_base_name')->nullable();
            $table->string('long_base_name')->nullable();
            $table->string('affiliation_id', 8)->unique()->nullable();
            $table->integer('current_step')->default(1);
            $table->datetime('date_initiated')->nullable();
            $table->dateTime('step_1_received_date')->nullable();
            $table->dateTime('step_1_approval_date')->nullable();
            $table->dateTime('step_2_approval_date')->nullable();
            $table->dateTime('step_3_approval_date')->nullable();
            $table->dateTime('step_4_received_date')->nullable();
            $table->dateTime('step_4_approval_date')->nullable();
            $table->datetime('date_completed')->nullable();
            $table->string('coi_code', 24)->unique();
            $table->string('hypothesis_group')->nullable();
            $table->text('membership_description')->nullable();
            $table->text('scope_description')->nullable();
            $table->dateTime('nhgri_attestation_date')->nullable();
            $table->foreignId('curation_review_protocol_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ;
            $table->text('curation_review_protocol_other')->nullable();
            $table->string('meeting_frequency')->nullable();
            $table->boolean('reanalysis_conflicting')->nullable();
            $table->boolean('reanalysis_review_lp')->nullable();
            $table->boolean('reanalysis_review_lb')->nullable();
            $table->text('reanalysis_other')->nullable();
            $table->dateTime('reanalysis_attestation_date')->nullable();

            $table->boolean('utilize_gt')->nullable();
            $table->boolean('utilize_gci')->nullable();
            $table->boolean('curations_publicly_available')->nullable();
            $table->boolean('pub_policy_reviewed')->nullable();
            $table->boolean('draft_manuscripts')->nullable();
            $table->boolean('recuration_process_review')->nullable();
            $table->boolean('biocurator_training')->nullable();
            $table->boolean('biocurator_mailing_list')->nullable();
            $table->dateTime('gci_training_date')->nullable();
            $table->dateTime('gcep_attestation_date')->nullable();
    

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
