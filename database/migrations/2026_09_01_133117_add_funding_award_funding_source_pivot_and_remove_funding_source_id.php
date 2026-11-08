<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        DB::table('funding_awards')->whereNotNull('funding_source_id')->orderBy('id')->chunkById(500, function ($awards) {
            DB::table('funding_award_funding_source')->insert($awards->map(fn ($award) => [
                'funding_award_id' => $award->id,
                'funding_source_id' => $award->funding_source_id,
                'created_at' => $award->created_at,
                'updated_at' => $award->updated_at,
            ])->all());
        });

        Schema::table('funding_awards', function (Blueprint $table) {
            $table->dropForeign(['funding_source_id']);
            $table->dropIndex(['funding_source_id', 'start_date']);
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
            $table->index(['funding_source_id', 'start_date']);
        });

        Schema::dropIfExists('funding_award_funding_source');
    }
};
