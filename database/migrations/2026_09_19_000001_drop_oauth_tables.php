<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The oauth_* tables created by long-deleted copies of Passport's migrations
 * were never used (GPM was only ever an OAuth client of GeneTracker). Drop
 * them; 2026_09_20_000001_create_passport_tables recreates the current
 * Passport schema for the client-credentials grant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('oauth_personal_access_clients');
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('oauth_refresh_tokens');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
    }

    public function down(): void
    {
        // Intentionally empty: the old-schema tables were unused and their
        // original migrations have been removed.
    }
};
