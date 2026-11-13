<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            // Annual updates can be cascade-deleted with their EP/window/submitter.
            // Retain publications rather than cascading their deletion.
            $table->foreignId('annual_update_id')->nullable()->index()->constrained('annual_updates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropForeign(['annual_update_id']);
            $table->dropIndex(['annual_update_id']);
            $table->dropColumn('annual_update_id');
        });
    }
};
