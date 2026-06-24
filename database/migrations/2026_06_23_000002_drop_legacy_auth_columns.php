<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authentication is now handled by Clerk, so the two-factor columns and the
 * password reset table (both owned by the removed Fortify/Sanctum setup) are no
 * longer used.
 *
 * The `password` and `remember_token` columns are intentionally retained for
 * now: a few admin CLI tools still reference them. They can be dropped in a
 * later migration once that tooling is reworked.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes']);
            });
        }

        Schema::dropIfExists('password_resets');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->after('password')->nullable();
            $table->text('two_factor_recovery_codes')->after('two_factor_secret')->nullable();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
