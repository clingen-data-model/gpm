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
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('people', function (Blueprint $table) {
                $table->index(['institution_id'], 'inst_id_idx');
                $table->fullText(['first_name', 'last_name'], 'fulltext_name');
                $table->fullText(['email'], 'fulltext_email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('people', function (Blueprint $table) {
                $table->dropIndex('inst_id_idx');
                $table->dropFullText('fulltext_name');
                $table->dropFullText('fulltext_email');
            });
        }
    }
};
