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
            $table->biginteger('birth_country')->unsigned()->nullable();
            $table->foreign('birth_country')->references('id')->on('countries');
            $table->biginteger('reside_country')->unsigned()->nullable();
            $table->foreign('reside_country')->references('id')->on('countries');

            $table->char('birth_year', 4);

            $table->string('grant_detail')->nullable();
            $table->string('support_other')->nullable();

            $table->string('disadvantaged');
            $table->boolean('state_opt_out')->nullable();
            $table->boolean('birth_country_opt_out')->nullable();
            $table->boolean('reside_country_opt_out')->nullable();
            $table->boolean('support_opt_out')->nullable();
            $table->boolean('ethnicity_opt_out')->nullable();
            $table->json('ethnicities')->nullable();
            $table->json('occupations')->nullable();
            $table->json('identities')->nullable();
            $table->json('gender_identities')->nullable();
            $table->json('specialty')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('birth_country')
                ->dropColumn('reside_country')
                ->dropColumn('birth_year')
                ->dropColumn('grant_detail')
                ->dropColumn('support_other')
                ->dropColumn('disadvantaged')
                ->dropColumn('state_opt_out')
                ->dropColumn('birth_country_opt_out')
                ->dropColumn('reside_country_opt_out')
                ->dropColumn('support_opt_out')
                ->dropColumn('ethnicity_opt_out')
                ->dropColumn('ethnicities')
                ->dropColumn('occupations')
                ->dropColumn('identities')
                ->dropColumn('gender_identities')
                ->dropColumn('specialty');
        });
        //
    }
};
