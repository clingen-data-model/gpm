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
        Schema::create('funding_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_panel_id')->constrained('expert_panels')->cascadeOnDelete();
            $table->foreignId('funding_source_id')->constrained('funding_sources')->restrictOnDelete();

            $table->string('award_number', 30)->nullable();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('nih_reporter_url')->nullable();
            $table->string('nih_ic')->nullable();

            $table->string('contact_1_role')->nullable();
            $table->string('contact_1_name')->nullable();
            $table->string('contact_1_email')->nullable();
            $table->string('contact_1_phone')->nullable();

            $table->string('contact_2_role')->nullable();
            $table->string('contact_2_name')->nullable();
            $table->string('contact_2_email')->nullable();
            $table->string('contact_2_phone')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['expert_panel_id', 'start_date']);
            $table->index(['funding_source_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_awards');
    }
};
