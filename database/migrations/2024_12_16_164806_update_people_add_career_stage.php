<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('career_stage', 255)->nullable();
            $table->text('career_stage_other')->nullable();
            $table->boolean('career_stage_opt_out')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('career_stage');
            $table->dropColumn('career_stage_other');
            $table->dropColumn('career_stage_opt_out');
        });
    }
};
