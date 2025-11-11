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
        Schema::create('attestations', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->enum('experience_type', ['direct_experience', 'detailed_review', 'fifty_variants_supervised', 'other'])->nullable();
            $table->text('other_text')->nullable();

            $table->string('attestation_version')->nullable();
            $table->foreignId('attested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attested_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Uniqueness: one active per person (not revoked, not soft-deleted)
            $table->unsignedBigInteger('active_person_id')->nullable()->storedAs('IF(`revoked_at` IS NULL AND `deleted_at` IS NULL, `person_id`, NULL)');
            $table->unique('active_person_id', 'uniq_active_attestation_per_person');
            
            $table->index(['person_id', 'attestation_version']);
            $table->index('attested_at');
            $table->index('revoked_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
