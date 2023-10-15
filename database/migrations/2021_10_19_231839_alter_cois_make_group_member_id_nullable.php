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
        Schema::table('cois', function (Blueprint $table) {
            $table->foreignId('group_member_id')
                ->nullable()
                ->cascadeOnDelete()
                ->cascadOnUpdate()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->foreignId('group_member_id')
                ->cascadeOnDelete()
                ->cascadOnUpdate()
                ->change();
        });
    }
};
