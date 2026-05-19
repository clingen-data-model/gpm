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
        Schema::create('scope_of_work_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expert_panel_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('major_version');
            $table->unsignedInteger('minor_version')->default(0);

            $table->string('status')->default('draft');
            // draft, submitted, revisions_requested, approved, rejected, discarded

            $table->foreignId('base_version_id')->nullable()->constrained('scope_of_work_versions')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('submissions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id', 'status'], 'sow_versions_group_status_idx');
            $table->index(['group_id', 'major_version', 'minor_version'], 'sow_versions_group_version_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_of_work_versions');
    }
};
