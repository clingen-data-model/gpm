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
        Schema::create('funding_award_contact_pis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_award_id')->constrained('funding_awards')->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->restrictOnDelete();
            $table->boolean('is_primary')->default(false);

            $table->timestamps();
            $table->unique(['funding_award_id', 'person_id'], 'fa_pi_unique');
            $table->index('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_award_contact_pis');
    }
};
