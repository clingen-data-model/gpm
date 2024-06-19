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
        Schema::table('people', function (Blueprint $table) {
            $table->integer('birth_country')->nullable();
            $table->string('birth_country_other', 255)->nullable();
            $table->boolean('birth_country_opt_out')->nullable();
            $table->integer('reside_country')->nullable();
            $table->string('reside_country_other', 255)->nullable();
            $table->boolean('reside_country_opt_out')->nullable();
            $table->char('reside_state', 2)->nullable();
            $table->boolean('reside_state_opt_out')->nullable();
            $table->json('ethnicities')->nullable();
            $table->string('ethnicity_other', 255)->nullable();
            $table->boolean('ethnicity_opt_out')->nullable();
            $table->year('birth_year')->nullable();
            $table->boolean('birth_year_opt_out')->nullable();
            $table->json('identities')->nullable();
            $table->string('identity_other', 255)->nullable();
            $table->boolean('identity_opt_out')->nullable();
            $table->json('gender_identities')->nullable();
            $table->string('gender_identities_other', 255)->nullable();
            $table->boolean('gender_identities_opt_out')->nullable();
            $table->json('support')->nullable();
            $table->string('grant_detail', 255)->nullable();
            $table->boolean('support_opt_out')->nullable();
            $table->string('support_other', 255)->nullable();
            $table->string('disadvantaged', 255)->nullable();
            $table->string('disadvantaged_other', 255)->nullable();
            $table->boolean('disadvantaged_opt_out')->nullable();
            $table->json('occupations')->nullable();
            $table->string('occupations_other', 255)->nullable();
            $table->boolean('occupations_opt_out')->nullable();
            $table->string('specialty', 255)->nullable();
            $table->date('demographics_completed_date')->nullable();
            $table->integer('demographics_version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('birth_country');
            $table->dropColumn('birth_country_other', 255);
            $table->dropColumn('birth_country_opt_out');
            $table->dropColumn('reside_country');
            $table->dropColumn('reside_country_other');
            $table->dropColumn('reside_country_opt_out');
            $table->dropColumn('reside_state');
            $table->dropColumn('reside_state_opt_out');
            $table->dropColumn('ethnicities');
            $table->dropColumn('ethnicity_other');
            $table->dropColumn('ethnicity_opt_out');
            $table->dropColumn('birth_year');
            $table->dropColumn('birth_year_opt_out');
            $table->dropColumn('identities');
            $table->dropColumn('identity_other');
            $table->dropColumn('identity_opt_out');
            $table->dropColumn('gender_identities');
            $table->dropColumn('gender_identities_other');
            $table->dropColumn('gender_identities_opt_out');
            $table->dropColumn('support');
            $table->dropColumn('grant_detail');
            $table->dropColumn('support_opt_out');
            $table->dropColumn('support_other');
            $table->dropColumn('disadvantaged');
            $table->dropColumn('disadvantaged_other');
            $table->dropColumn('disadvantaged_opt_out');
            $table->dropColumn('occupations');
            $table->dropColumn('occupations_other');
            $table->dropColumn('occupations_opt_out');
            $table->dropColumn('specialty');
            $table->dropColumn('demographics_completed_date');
            $table->dropColumn('demographics_version');
    }
};
