<?php

use Database\Seeders\GroupTypeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('group_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('fullname')->unique();
            $table->text('description');
            $table->boolean('can_be_parent')->boolean()->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();

        (new GroupTypeSeeder)->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('group_types');
    }
};
