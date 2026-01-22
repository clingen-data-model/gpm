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
        if (Schema::hasTable('genes') && !Schema::hasTable('scope_genes')) {
            Schema::rename('genes', 'scope_genes');
        }
        if (!Schema::hasTable('scope_gene_snapshots')) {
            Schema::create('scope_gene_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scope_gene_id');
                $table->uuid('curation_uuid')->nullable()->index();
                $table->string('check_key', 64)->nullable();
                $table->json('payload');
                $table->timestamp('captured_at')->useCurrent();
                $table->boolean('is_outdated')->default(false)->index();
                $table->timestamp('last_compared_at')->nullable();
                $table->timestamps();
                $table->unique(['scope_gene_id', 'curation_uuid']);
                $table->foreign('scope_gene_id')->references('id')->on('scope_genes')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_gene_snapshots');

        if (Schema::hasTable('scope_genes') && !Schema::hasTable('genes')) {
            Schema::rename('scope_genes', 'genes');
        }
    }
};
