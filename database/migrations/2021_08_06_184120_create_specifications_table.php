<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('specifications', function (Blueprint $table) {
            $table->string('cspec_id')->primary();
            $table->foreignId('expert_panel_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('status_id')->constrained('specification_statuses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('url')->nullable();
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
        Schema::dropIfExists('specifcations');
    }
}
