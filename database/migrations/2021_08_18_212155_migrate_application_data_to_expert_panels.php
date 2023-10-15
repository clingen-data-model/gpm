<?php

use App\Actions\DataMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DataMigration::run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('expert_panels')->delete();
    }
};
