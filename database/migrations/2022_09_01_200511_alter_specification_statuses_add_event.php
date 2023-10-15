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
        Schema::table('specification_statuses', function (Blueprint $table) {
            $table->string('event')->after('name');
            $table->string('color')->after('description');
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('specification_statuses', function (Blueprint $table) {
            $table->dropIndex(['event']);
            $table->dropColumn('event');
            $table->dropColumn('color');
        });
    }
};
