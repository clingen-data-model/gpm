<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->text('birth_country_other')->nullable()->change();
            $table->text('reside_country_other')->nullable()->change();
            $table->text('ethnicity_other')->nullable()->change();
            $table->text('identity_other')->nullable()->change();
            $table->text('gender_identities_other')->nullable()->change();
            $table->text('grant_detail')->nullable()->change();
            $table->text('support_other')->nullable()->change();
            $table->text('disadvantaged_other')->nullable()->change();
            $table->text('occupations_other')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('birth_country_other', 255)->nullable()->change();
            $table->string('reside_country_other', 255)->nullable()->change();
            $table->string('ethnicity_other', 255)->nullable()->change();
            $table->string('identity_other', 255)->nullable()->change();
            $table->string('gender_identities_other', 255)->nullable()->change();
            $table->string('grant_detail', 255)->nullable()->change();
            $table->string('support_other', 255)->nullable()->change();
            $table->string('disadvantaged_other', 255)->nullable()->change();
            $table->string('occupations_other', 255)->nullable()->change();
        });
    }
};
