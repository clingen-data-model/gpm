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
        Schema::table('group_types', function ($table) {
            $table->tinyInteger('is_expert_panel')->default(false);
            $table->tinyInteger('curates_variants')->default(false);
            $table->tinyInteger('is_somatic_cancer')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_types', function ($table) {
            $table->dropColumn('is_expert_panel');
            $table->dropColumn('creates_variants');
            $table->dropColumn('is_somatic_cancer');
        });
    }
};
