<?php

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
        if (! Schema::hasColumn('group_members', 'v1_contact')) {
            return;
        }
        Schema::table('group_members', function (Blueprint $table) {
            $table->renameColumn('v1_contact', 'is_contact');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasColumn('group_members', 'v1_contact')) {
            return;
        }
        Schema::table('group_members', function (Blueprint $table) {
            $table->renameColumn('is_contact', 'v1_contact');
        });
    }
};
