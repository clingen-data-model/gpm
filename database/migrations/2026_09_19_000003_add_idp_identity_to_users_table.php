<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a local user to an identity at an external identity provider as a
 * (provider, id) pair so additional providers can be added later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('idp_provider', 32)->nullable()->after('remember_token');
            $table->string('idp_id', 191)->nullable()->after('idp_provider');

            $table->unique(['idp_provider', 'idp_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['idp_provider', 'idp_id']);
            $table->dropColumn(['idp_provider', 'idp_id']);
        });
    }
};
