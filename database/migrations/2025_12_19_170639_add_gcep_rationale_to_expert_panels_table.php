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
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->text('gcep_rationale')->nullable()->after('scope_description'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expert_panels', function (Blueprint $table) {
            $table->dropColumn('gcep_rationale');
        });
    }
};
