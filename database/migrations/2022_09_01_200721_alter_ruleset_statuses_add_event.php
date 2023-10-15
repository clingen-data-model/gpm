<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('ruleset_statuses', function (Blueprint $table) {
            $table->string('event')->after('name');
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('ruleset_statuses', function (Blueprint $table) {
            $table->dropIndex(['event']);
            $table->dropColumn('event');
        });
    }
};
