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
            Schema::table('institutions', function (Blueprint $table) {
                $table->fullText(['name', 'abbreviation'], 'search_fulltext');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('institutions', function (Blueprint $table) {
                $table->dropFullText('search_fulltext');
            });
        }
    }
};
