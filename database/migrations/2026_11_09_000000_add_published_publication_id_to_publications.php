<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->foreignId('published_publication_id')->nullable()->unique()->constrained('publications')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropForeign(['published_publication_id']);
            $table->dropUnique(['published_publication_id']);
            $table->dropColumn('published_publication_id');
        });
    }
};
