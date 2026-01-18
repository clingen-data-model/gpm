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
            $table->dropForeign(['primary_occupation_id']);
            $table->dropForeign(['ethnicity_id']);
            $table->dropForeign(['gender_id']);
            $table->dropForeign(['race_id']);
            $table->dropColumn([
                'primary_occupation_id',
                'primary_occupation_other',
                'ethnicity_id',
                'gender_id',
                'gender_other',
                'race_id',
                'race_other',
            ]);
            //
        });
        Schema::dropIfExists('primary_occupations');
        Schema::dropIfExists('races');
        Schema::dropIfExists('ethnicities');
        Schema::dropIfExists('genders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the dropped tables
        Schema::create('primary_occupations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('ethnicities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('genders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Re-add the dropped columns to people table
        Schema::table('people', function (Blueprint $table) {
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
};
