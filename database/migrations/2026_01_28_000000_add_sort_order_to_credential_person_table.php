<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSortOrderToCredentialPersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('credential_person', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('person_id')->nullable(false);
        });

        // Populate sort_order for existing records based on credential_id-- will need to fix this but it at least matched prior behavior
        DB::statement("
            UPDATE credential_person cp
            JOIN (
                SELECT person_id, credential_id, ROW_NUMBER() OVER (PARTITION BY person_id ORDER BY credential_id ASC) as credrank
                FROM credential_person
            ) ordered_set ON cp.person_id = ordered_set.person_id AND cp.credential_id = ordered_set.credential_id
            SET cp.sort_order = ordered_set.credrank - 1
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('credential_person', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
}
