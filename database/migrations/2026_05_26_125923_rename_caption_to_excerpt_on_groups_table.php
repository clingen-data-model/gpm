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
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('caption', 'excerpt');
            $table->text('excerpt')->nullable()->change();
            $table->dropColumn('icon_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->renameColumn('excerpt', 'caption');
            $table->string('icon_path')->nullable();
        });
    }
};
