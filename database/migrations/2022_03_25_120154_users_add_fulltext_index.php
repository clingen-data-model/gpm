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
            Schema::table('users', function (Blueprint $table) {
                $table->fullText(['name'], 'name_fulltext');
                $table->fullText(['email'], 'email_fulltext');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropFullText('name_fulltext');
                $table->dropFullText('email_fulltext');
            });
        }
    }
};
