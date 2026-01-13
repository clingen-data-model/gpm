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
        Schema::create('funding_sources', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->foreignId('funding_type_id')->constrained('funding_types')->restrictOnDelete();
            $table->text('caption', 500)->nullable();
            $table->string('website_url')->nullable();

            $table->string('logo_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('funding_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_sources');
    }
};
