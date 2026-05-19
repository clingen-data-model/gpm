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
        Schema::create('scope_of_work_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_of_work_version_id')->constrained('scope_of_work_versions')->cascadeOnDelete();

            $table->string('rule_key')->nullable();

            $table->string('area');
            $table->string('change_type');
            $table->string('label')->nullable();

            $table->string('entity_type')->nullable();
            $table->string('entity_uuid')->nullable();
            $table->string('entity_label')->nullable();

            $table->string('field_name')->nullable();

            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();

            $table->string('impact')->nullable();
            $table->string('requires_approval')->nullable();
            $table->unsignedInteger('approval_step')->nullable();
            $table->string('approvers')->nullable();
            $table->string('condition')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('scope_of_work_version_id');
            $table->index(['area', 'change_type']);
            $table->index(['impact', 'requires_approval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_of_work_changes');
    }
};
