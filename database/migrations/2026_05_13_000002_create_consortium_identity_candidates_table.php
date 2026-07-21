<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consortium_identity_candidates', function (Blueprint $table) {
            $table->id();

            $table->string('import_batch_uuid', 100)->index();
            $table->uuid('candidate_uuid')->unique();

            $table->char('gpm_uuid', 36)->nullable()->index();
            $table->char('resolved_gpm_uuid', 36)->nullable()->index();

            $table->string('clerk_user_id')->nullable()->index();
            $table->string('clerk_import_status', 50)->nullable()->index();
            $table->text('clerk_import_error')->nullable();
            $table->timestamp('clerk_imported_at')->nullable();
            $table->json('clerk_response')->nullable();

            $table->string('canonical_email')->nullable()->index();
            $table->string('canonical_full_name')->nullable();
            $table->string('canonical_first_name')->nullable();
            $table->string('canonical_last_name')->nullable();

            $table->text('password_digest')->nullable();
            $table->string('password_hasher', 50)->nullable();
            $table->string('password_source_system', 50)->nullable()->index('cic_password_source_idx');

            $table->string('matched_by', 50)->nullable()->index(); // gpm_uuid / exact_email / exact_name / single_row
            $table->string('identity_status', 50)->default('candidate')->index(); // candidate / manual_review / ready / skipped
            $table->string('recommended_action', 50)->nullable()->index(); // link_existing_gpm / create_new_gpm_uuid / manual_review / skip

            $table->boolean('exact_email_cross_system')->default(false)->index('cic_exact_email_cross_idx');
            $table->boolean('same_name_diff_email_cross_system')->default(false)->index('cic_same_name_diff_email_idx');
            $table->boolean('has_existing_gpm_uuid')->default(false)->index('cic_has_gpm_uuid_idx');
            $table->boolean('needs_manual_review')->default(false)->index('cic_needs_review_idx');
            

            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('source_system_count')->default(0);

            $table->json('source_systems')->nullable();
            $table->json('source_row_ids')->nullable();
            $table->json('local_ids_by_system')->nullable();
            $table->json('emails')->nullable();
            $table->json('names')->nullable();
            $table->json('flags')->nullable();

            $table->text('review_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consortium_identity_candidates');
    }
};