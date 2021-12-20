<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToPeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()
                ->constrained()
                ->after('email');
            $table->foreignId('institution_id')->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->after('phone');
                
            $table->text('credentials')->nullable()->after('institution_id');
            $table->text('biography')->nullable()->after('credentials');
            $table->string('profile_photo_path')->nullable()->after('biography');
            
            $table->string('orcid_id')->nullable()->after('profile_photo_path');
            $table->string('hypothesis_id')->nullable()->after('orcid_id');
            
            $table->string('street1')->nullable()->after('hypothesis_id');
            $table->string('street2')->nullable()->after('street1');
            $table->string('city')->nullable()->after('street2');
            $table->string('state')->nullable()->after('city');
            $table->string('zip')->nullable()->after('state');
            $table->foreignId('country_id')->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->after('zip');
            $table->string('timezone')->nullable()->after('country_id');
            
            $table->foreignId('primary_occupation_id')->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->after('timezone');
            $table->string('primary_occupation_other')->nullable()->after('primary_occupation_id');
            
            $table->foreignId('race_id')->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->after('primary_occupation_other');
            $table->string('race_other')->nullable()->after('race_id');
            $table->foreignId('ethnicity_id')->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->after('race_other');
            $table->foreignId('gender_id')->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->after('ethnicity_id');
            $table->string('gender_other')->nullable()->after('gender_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropColumn('institution_id');
            $table->dropColumn('credentials');
            $table->dropColumn('biography');
            $table->dropColumn('profile_photo_path');
            $table->dropColumn('orcid_id');
            $table->dropColumn('hypothesis_id');
            $table->dropColumn('street1');
            $table->dropColumn('street2');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('zip');
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
            $table->dropColumn('timezone');
            $table->dropForeign(['primary_occupation_id']);
            $table->dropColumn('primary_occupation_id');
            $table->dropColumn('primary_occupation_other');
            $table->dropForeign(['race_id']);
            $table->dropColumn('race_id');
            $table->dropColumn('race_other');
            $table->dropForeign(['ethnicity_id']);
            $table->dropColumn('ethnicity_id');
            $table->dropForeign(['gender_id']);
            $table->dropColumn('gender_id');
            $table->dropColumn('gender_other');
        });
    }
}
