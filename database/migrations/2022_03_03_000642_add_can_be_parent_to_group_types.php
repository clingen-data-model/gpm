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
        if (Schema::hasColumn('group_types', 'can_be_parent')) {
            return;
        }
        Schema::table('group_types', function (Blueprint $table) {
            $table->boolean('can_be_parent')->boolean()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_types', function (Blueprint $table) {
            $table->dropColumn('can_be_parent');
        });
    }
};
