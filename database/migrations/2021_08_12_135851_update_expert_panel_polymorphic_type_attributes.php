<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DB::table('activity_log')
        //     ->where('subject_type', 'App\Modules\Application\Models\Application')
        //     ->update(['subject_type' =>'App\Modules\ExpertPanel\Models\ExpertPanel']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DB::table('activity_log')
        //     ->where('subject_type', 'App\Modules\Application\Models\ExpertPanel')
        //     ->update(['subject_type' =>'App\Modules\Application\Models\Application']);
    }
};
