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
        Schema::create('scope_of_work_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_of_work_version_id')->constrained('scope_of_work_versions')->cascadeOnDelete();
            $table->string('snapshot_schema_version')->default('1.0.0');
            $table->json('snapshot');

            $table->timestamps();
            $table->softDeletes();

            $table->index('scope_of_work_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_of_work_snapshots');
    }
};
