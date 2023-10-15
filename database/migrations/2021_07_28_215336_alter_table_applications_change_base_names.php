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
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['short_base_name']);
            $table->dropUnique(['long_base_name']);

            $table->unique(['short_base_name', 'ep_type_id']);
            $table->unique(['long_base_name', 'ep_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['short_base_name', 'ep_type_id']);
            $table->dropUnique(['long_base_name', 'ep_type_id']);

            $table->unique('short_base_name');
            $table->unique('long_base_name');
        });
    }
};
