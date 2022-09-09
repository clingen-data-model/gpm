<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSpecificationAndRulesetStatusColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('specification_rulesets');
        Schema::dropIfExists('specifications');

        Schema::create('specifications', function (Blueprint $table) {
            $table->string('cspec_id')->primary();
            $table->foreignId('expert_panel_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('status');
            $table->string('url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('specification_rulesets', function (Blueprint $table) {
            $table->string('cspec_ruleset_id')->primary();
            $table->string('specification_id');
            $table->foreign('specification_id')->references('cspec_id')->on('specifications')->onDelete('cascade')->onUpdate('cascade');
            $table->string('status');
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
        Schema::table('specifications', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id');
            $table->foreign('status_id')->references('id')->on('specification_statuses');
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
}
