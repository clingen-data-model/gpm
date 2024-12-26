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
        Schema::dropIfExists('biocurator_onboarding_attestations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('biocurator_onboarding_attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_panel_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->json('data')->nullable();
            $table->dateTime('attested_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }
};
