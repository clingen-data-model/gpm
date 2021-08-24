<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecificationRuleSetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('specification_rule_sets', function (Blueprint $table) {
            $table->string('cspec_ruleset_id')->primary();
            $table->string('specification_id');
            $table->foreign('specification_id')->references('cspec_id')->on('specifications')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('status_id')->constrained('specification_ruleset_statuses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('specification_rule_sets');
    }
}
