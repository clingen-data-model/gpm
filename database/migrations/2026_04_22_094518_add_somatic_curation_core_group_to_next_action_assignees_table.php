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
        DB::table('next_action_assignees')->updateOrInsert(
            ['id' => 6],
            [
                'name' => 'Somatic Curation Core Group',
                'short_name' => 'SC Core',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('next_action_assignees')
            ->where('id', 6)
            ->delete();
    }
};