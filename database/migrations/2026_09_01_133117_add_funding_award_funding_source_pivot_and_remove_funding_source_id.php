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
        Schema::create('funding_award_funding_source', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_award_id')->constrained('funding_awards')->cascadeOnDelete();
            $table->foreignId('funding_source_id')->constrained('funding_sources')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['funding_award_id', 'funding_source_id'], 'funding_award_source_unique');
        });

        Schema::table('funding_awards', function (Blueprint $table) {
            $table->dropForeign(['funding_source_id']);
            $table->dropColumn('funding_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funding_awards', function (Blueprint $table) {
            $table->foreignId('funding_source_id')->nullable()->constrained('funding_sources');
        });

        Schema::dropIfExists('funding_award_funding_source');
    }
};
