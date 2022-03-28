<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersAddFulltextIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
     *
     * @return void
     */
    public function down()
    {
        if (config('database.connections')[config('database.default')]['driver'] != 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropFullText('name_fulltext');
                $table->dropFullText('email_fulltext');
            });
        }
    }
}
