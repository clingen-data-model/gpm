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
           $table->integer('reside_country')->nullable();
            $table->year('birth_year')->nullable();
            $table->char('reside_state', 2)->nullable();
            $table->string('grant_detail', 255)->nullable();
            $table->string('support_other', 255)->nullable();  
            $table->string('disadvantaged', 255)->nullable();
            $table->boolean('reside_state_opt_out')->nullable();
            $table->boolean('birth_country_opt_out')->nullable();
            $table->boolean('reside_country_opt_out')->nullable();
            $table->boolean('support_opt_out')->nullable();
            $table->boolean('birth_year_opt_out')->nullable();
            $table->boolean('ethnicity_opt_out')->nullable();
            $table->json('ethnicities')->nullable();
            $table->json('support')->nullable();
            $table->json('occupations')->nullable();
           $table->json('identities')->nullable();
            $table->json('gender_identities')->nullable();
            $table->string('specialty', 255)->nullable();
            $table->boolean('identity_opt_out')->nullable();
            $table->boolean('gender_identities_opt_out')->nullable();
            $table->boolean('disadvantaged_opt_out')->nullable();
            $table->boolean('occupations_opt_out')->nullable();
            $table->integer('demographics_version')->nullable();
            $table->date('demographics_completed_date')->nullable();

            $table->string('disadvantaged_other', 255)->nullable();
            $table->string('identity_other', 255)->nullable();
            $table->string('occupations_other', 255)->nullable();
            $table->string('birth_country_other', 255)->nullable();
            $table->string('reside_country_other', 255)->nullable();
            $table->string('gender_identities_other', 255)->nullable();
          //  $table->char('gender_preferred_term', 30)->nullable();
            $table->string('ethnicity_other', 255)->nullable();

           





        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
