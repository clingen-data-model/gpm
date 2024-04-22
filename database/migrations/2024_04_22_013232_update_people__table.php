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
    {Schema::table('people', function (Blueprint $table) {
      
$table->char('disadvantaged_other', 25)->nullable();
$table->char('identity_other', 25)->nullable();
$table->char('occupations_other', 25)->nullable();
$table->char('birth_country_other', 25)->nullable();
$table->char('reside_country_other', 25)->nullable();
$table->char('gender_identities_other', 25)->nullable();
$table->boolean('demo_form_complete')->nullable();
$table->char('gender_preferred_term', 30)->nullable();


    });
    //
}
        //
  //  }
//}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
