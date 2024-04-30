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
            $table->string('disadvantaged_other')->nullable();
            $table->string('identity_other')->nullable();
            $table->string('occupations_other')->nullable();
            $table->string('birth_country_other')->nullable();
            $table->string('reside_country_other')->nullable();
            $table->string('gender_identities_other')->nullable();
            $table->boolean('demo_form_complete')->nullable();
            $table->string('gender_preferred_term')->nullable();
            $table->string('support')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('disadvantaged_other');
            $table->dropColumn('identity_other');
            $table->dropColumn('occupations_other');
            $table->dropColumn('birth_country_other');
            $table->dropColumn('reside_country_other');
            $table->dropColumn('gender_identities_other');
            $table->dropColumn('demo_form_complete');
            $table->dropColumn('gender_preferred_term');
            $table->dropColumn('support');
        });
    }
};
