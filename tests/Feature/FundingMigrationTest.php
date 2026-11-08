<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\CreatesApplication;

class FundingMigrationTest extends TestCase
{
    use CreatesApplication;

    public function test_pivot_migration_backfills_legacy_sources_before_dropping_column(): void
    {
        // Exercise DDL outside RefreshDatabase's transaction, as migrations run on SQLite.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['expert_panels', 'funding_sources'] as $table) {
            Schema::create($table, fn (Blueprint $table) => $table->id());
            DB::table($table)->insert(['id' => 1]);
        }
        (require database_path('migrations/2026_01_11_160321_create_funding_awards_table.php'))->up();
        DB::table('funding_awards')->insert([
            'id' => 1, 'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'expert_panel_id' => 1, 'funding_source_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $migration = require database_path('migrations/2026_09_01_133117_add_funding_award_funding_source_pivot_and_remove_funding_source_id.php');
        $migration->up();
        $this->assertFalse(Schema::hasColumn('funding_awards', 'funding_source_id'));
        $this->assertDatabaseHas('funding_award_funding_source', ['funding_award_id' => 1, 'funding_source_id' => 1]);
        $migration->down();
        $this->assertTrue(Schema::hasColumn('funding_awards', 'funding_source_id'));
        $this->assertFalse(Schema::hasTable('funding_award_funding_source'));
    }
}
