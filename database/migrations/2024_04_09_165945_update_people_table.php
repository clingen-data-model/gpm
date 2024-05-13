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
            $table->char('birth_year', 4)->nullable();
            $table->char('reside_state', 2)->nullable();
            $table->char('grant_detail', 30)->nullable();
            $table->char('support_other', 30)->nullable();  
            $table->char('disadvantaged', 15);
            $table->boolean('state_opt_out')->nullable();
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
            $table->char('specialty', 50)->nullable();
            
            $table->boolean('identity_opt_out')->nullable();
            $table->boolean('gender_identities_opt_out')->nullable();
            $table->boolean('disadvantaged_opt_out')->nullable();
            $table->boolean('occupations_opt_out')->nullable();
            $table->integer('demographics_version')->nullable();
            $table->date('demographics_completed_date')->nullable();
           





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
