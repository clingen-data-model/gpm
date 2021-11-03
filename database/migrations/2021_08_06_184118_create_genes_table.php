<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGenesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('genes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hgnc_id');
            // $table->foreignId('hgnc_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate()->primary();
            $table->foreignId('expert_panel_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('gene_symbol');
            $table->string('mondo_id', 14)->nullable();
            $table->string('disease_name')->nullable();
            $table->dateTime('date_approved')->nullable();
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
        Schema::dropIfExists('genes');
    }
}
