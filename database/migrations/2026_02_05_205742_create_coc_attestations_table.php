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
        Schema::create('coc_attestations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();

            $table->string('version', 10);
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('expires_at')->nullable();

            $table->longText('data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['person_id', 'completed_at']);
            $table->index(['person_id', 'expires_at']);
            $table->index(['person_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coc_attestations');
    }
};
