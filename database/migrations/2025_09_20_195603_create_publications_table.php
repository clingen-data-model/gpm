<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();

            $t->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            
            $t->foreignId('added_by_id')->nullable()->constrained('people')->nullOnDelete();
            $t->foreignId('updated_by_id')->nullable()->constrained('people')->nullOnDelete();

            $t->enum('source', ['pmid','pmcid','doi','url'])->index();
            $t->string('identifier'); 
            $t->json('meta')->nullable();
            $t->string('pub_type', 50)->nullable()->index();
            $t->date('published_at')->nullable()->index();

            $t->enum('status', ['pending','enriched','failed'])->default('pending')->index();
            $t->string('error')->nullable(); // last error message if any

            $t->timestamps();
            $t->softDeletes();

            $t->unique(['group_id','source','identifier']);
            $t->index(['group_id','published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};
