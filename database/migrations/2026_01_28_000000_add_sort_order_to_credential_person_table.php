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

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("
                UPDATE credential_person cp
                JOIN (
                    SELECT person_id, credential_id, ROW_NUMBER() OVER (PARTITION BY person_id ORDER BY credential_id ASC) as credrank
                    FROM credential_person
                ) ordered_set ON cp.person_id = ordered_set.person_id AND cp.credential_id = ordered_set.credential_id
                SET cp.sort_order = ordered_set.credrank - 1
            ");
        } else {
            DB::statement("
                UPDATE credential_person
                SET sort_order = (
                    SELECT COUNT(*) - 1
                    FROM credential_person cp2
                    WHERE cp2.person_id = credential_person.person_id
                    AND cp2.credential_id <= credential_person.credential_id
                )
            ");
        }
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
