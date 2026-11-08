<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn('city');
        });
    }
};
